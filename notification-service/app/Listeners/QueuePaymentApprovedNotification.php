<?php

namespace App\Listeners;

use App\Events\PaymentApprovedReceived;
use App\Jobs\ProcessPaymentApprovedNotification;

class QueuePaymentApprovedNotification
{
    public function handle(PaymentApprovedReceived $event): void
    {
        ProcessPaymentApprovedNotification::dispatch(
            $event->payload->paymentId,
            $event->payload->orderId,
            $event->payload->eventId,
            $event->payload->occurredAt,
        );
    }
}
