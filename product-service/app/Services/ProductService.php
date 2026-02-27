<?php

namespace App\Services;

use App\Contracts\ProductRepositoryInterface;
use App\Contracts\ProductServiceInterface;
use App\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProductService implements ProductServiceInterface
{
    private const CACHE_TTL = 3600;

    public function __construct(
        private ProductRepositoryInterface $productRepository
    ) {
    }

    public function getAll(): Collection
    {
        return Cache::remember('products.all', self::CACHE_TTL, function () {
            return $this->productRepository->all();
        });
    }

    public function getById(int $id): Product
    {
        $product = Cache::remember("products.{$id}", self::CACHE_TTL, function () use ($id) {
            return $this->productRepository->findById($id);
        });
        if (! $product) {
            throw (new ModelNotFoundException)->setModel(Product::class, $id);
        }
        return $product;
    }

    public function create(array $data): Product
    {
        $product = $this->productRepository->create($data);
        $this->clearProductCache($product->id);
        return $product;
    }

    public function update(int $id, array $data): Product
    {
        $product = $this->getById($id);
        $product = $this->productRepository->update($product, $data);
        $this->clearProductCache($id);
        return $product;
    }

    public function reserveStock(int $productId, int $quantity): array
    {
        try {
            DB::beginTransaction();

            $product = $this->productRepository->findByIdForUpdate($productId);
            if (! $product) {
                DB::rollBack();
                throw (new ModelNotFoundException)->setModel(Product::class, $productId);
            }

            if ($product->stock < $quantity) {
                DB::rollBack();
                return ['success' => false, 'message' => 'Insufficient stock'];
            }

            $product = $this->productRepository->updateStock($product, $quantity);
            $this->clearProductCache($productId);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Stock reserved successfully',
                'remaining_stock' => $product->stock,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function clearProductCache(?int $id = null): void
    {
        Cache::forget('products.all');
        if ($id !== null) {
            Cache::forget("products.{$id}");
        }
    }
}
