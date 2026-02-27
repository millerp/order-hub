<?php

namespace App\Repositories;

use App\Contracts\NotificationRepositoryInterface;
use App\Models\Notification;

class NotificationRepository implements NotificationRepositoryInterface
{
    public function create(array $data): Notification
    {
        return Notification::create($data);
    }

    public function findById(int $id): ?Notification
    {
        return Notification::find($id);
    }

    public function findByPaymentId(int $paymentId): ?Notification
    {
        return Notification::where('payment_id', $paymentId)->first();
    }

    public function update(Notification $notification, array $data): Notification
    {
        $notification->update($data);

        return $notification->fresh();
    }
}
