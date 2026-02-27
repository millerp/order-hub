<?php

namespace App\Repositories;

use App\Contracts\PaymentRepositoryInterface;
use App\Models\Payment;

class PaymentRepository implements PaymentRepositoryInterface
{
    public function create(array $data): Payment
    {
        return Payment::create($data);
    }

    public function findById(int $id): ?Payment
    {
        return Payment::find($id);
    }

    public function findByOrderId(int $orderId): ?Payment
    {
        return Payment::where('order_id', $orderId)->first();
    }

    public function update(Payment $payment, array $data): Payment
    {
        $payment->update($data);
        return $payment->fresh();
    }
}
