<?php

namespace App\Contracts;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

interface ProductServiceInterface
{
    public function getAll(): Collection;

    public function getById(int $id): Product;

    public function create(array $data): Product;

    public function update(int $id, array $data): Product;

    public function reserveStock(int $productId, int $quantity): array;
}
