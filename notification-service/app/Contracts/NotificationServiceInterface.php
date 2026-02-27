<?php

namespace App\Contracts;

use App\Models\Notification;

interface NotificationServiceInterface
{
    public function createForPayment(int $paymentId, int $orderId, string $type): Notification;

    public function getById(int $id): Notification;

    public function getByPaymentId(int $paymentId): ?Notification;

    public function updateStatus(int $id, string $status): Notification;
}
