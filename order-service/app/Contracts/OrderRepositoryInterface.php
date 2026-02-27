<?php

namespace App\Contracts;

use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;

interface OrderRepositoryInterface
{
    public function getByUserId(string $userId): Collection;

    public function create(array $data): Order;
}
