<?php

namespace App\Contracts;

use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;

interface OrderServiceInterface
{
    public function getOrdersByUser(string $userId): Collection;

    public function createOrder(array $data, ?string $bearerToken = null, ?string $traceId = null): Order;
}
