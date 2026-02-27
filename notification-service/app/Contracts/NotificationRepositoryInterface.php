<?php

namespace App\Contracts;

use App\Models\Notification;

interface NotificationRepositoryInterface
{
    public function create(array $data): Notification;

    public function findById(int $id): ?Notification;

    public function findByPaymentId(int $paymentId): ?Notification;

    public function update(Notification $notification, array $data): Notification;
}
