<?php

namespace App\Repositories;

use App\Contracts\ProductRepositoryInterface;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository implements ProductRepositoryInterface
{
    public function all(): Collection
    {
        return Product::all();
    }

    public function findById(int $id): ?Product
    {
        return Product::find($id);
    }

    public function findByIdForUpdate(int $id): ?Product
    {
        return Product::where('id', $id)->lockForUpdate()->first();
    }

    public function create(array $data): Product
    {
        return Product::create($data);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product->fresh();
    }

    public function updateStock(Product $product, int $quantity): Product
    {
        $product->stock -= $quantity;
        $product->save();

        return $product->fresh();
    }
}
