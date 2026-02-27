<?php

namespace App\Contracts;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

interface ProductRepositoryInterface
{
    public function all(): Collection;

    public function findById(int $id): ?Product;

    public function findByIdForUpdate(int $id): ?Product;

    public function create(array $data): Product;

    public function update(Product $product, array $data): Product;

    public function updateStock(Product $product, int $quantity): Product;
}
