<?php

namespace App\Repositories;

use App\Contracts\OrderRepositoryInterface;
use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;

class OrderRepository implements OrderRepositoryInterface
{
    public function getByUserId(string $userId): Collection
    {
        return Order::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function create(array $data): Order
    {
        return Order::create($data);
    }
}
