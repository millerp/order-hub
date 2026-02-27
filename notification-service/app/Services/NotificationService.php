<?php

namespace App\Services;

use App\Contracts\NotificationRepositoryInterface;
use App\Contracts\NotificationServiceInterface;
use App\Models\Notification;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class NotificationService implements NotificationServiceInterface
{
    public function __construct(
        private NotificationRepositoryInterface $notificationRepository
    ) {}

    public function createForPayment(int $paymentId, int $orderId, string $type): Notification
    {
        return $this->notificationRepository->create([
            'payment_id' => $paymentId,
            'order_id' => $orderId,
            'type' => $type,
            'status' => 'pending',
        ]);
    }

    public function getById(int $id): Notification
    {
        $notification = $this->notificationRepository->findById($id);
        if (! $notification) {
            throw (new ModelNotFoundException)->setModel(Notification::class, $id);
        }

        return $notification;
    }

    public function getByPaymentId(int $paymentId): ?Notification
    {
        return $this->notificationRepository->findByPaymentId($paymentId);
    }

    public function updateStatus(int $id, string $status): Notification
    {
        $notification = $this->getById($id);

        return $this->notificationRepository->update($notification, ['status' => $status]);
    }
}
