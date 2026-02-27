<?php

namespace App\Services;

use App\Contracts\PaymentRepositoryInterface;
use App\Contracts\PaymentServiceInterface;
use App\Models\Payment;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PaymentService implements PaymentServiceInterface
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository
    ) {}

    public function createForOrder(int $orderId, float $amount): Payment
    {
        return $this->paymentRepository->create([
            'order_id' => $orderId,
            'amount' => $amount,
            'status' => 'pending',
            'retry_count' => 0,
        ]);
    }

    public function getById(int $id): Payment
    {
        $payment = $this->paymentRepository->findById($id);
        if (! $payment) {
            throw (new ModelNotFoundException)->setModel(Payment::class, $id);
        }

        return $payment;
    }

    public function getByOrderId(int $orderId): ?Payment
    {
        return $this->paymentRepository->findByOrderId($orderId);
    }

    public function updateStatus(int $id, string $status): Payment
    {
        $payment = $this->getById($id);

        return $this->paymentRepository->update($payment, ['status' => $status]);
    }
}
