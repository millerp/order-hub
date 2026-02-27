<?php

namespace App\Contracts;

use App\Models\Payment;

interface PaymentRepositoryInterface
{
    public function create(array $data): Payment;

    public function findById(int $id): ?Payment;

    public function findByOrderId(int $orderId): ?Payment;

    public function update(Payment $payment, array $data): Payment;
}
