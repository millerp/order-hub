<?php

namespace App\Contracts;

use App\Models\Payment;

interface PaymentServiceInterface
{
    public function createForOrder(int $orderId, float $amount): Payment;

    public function getById(int $id): Payment;

    public function getByOrderId(int $orderId): ?Payment;

    public function updateStatus(int $id, string $status): Payment;
}
