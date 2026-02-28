<?php

namespace App\Listeners;

use App\Events\PaymentApprovedReceived;
use App\Jobs\CompensateNotificationFailure;
use App\Jobs\FinalizeNotificationDelivery;
use App\Jobs\ProcessPaymentApprovedNotification;
use Illuminate\Support\Facades\Bus;

class QueuePaymentApprovedNotification
{
    public function handle(PaymentApprovedReceived $event): void
    {
        $paymentId = $event->payload->paymentId;
        $orderId = $event->payload->orderId;
        $eventId = $event->payload->eventId;
        $occurredAt = $event->payload->occurredAt;
        $traceId = $event->payload->traceId;

        Bus::chain([
            new ProcessPaymentApprovedNotification(
                $paymentId,
                $orderId,
                $eventId,
                $occurredAt,
                $traceId,
            ),
            new FinalizeNotificationDelivery(
                $eventId,
            ),
        ])->catch(function (\Throwable $e) use ($paymentId, $orderId, $eventId, $occurredAt, $traceId): void {
            CompensateNotificationFailure::dispatch(
                $paymentId,
                $orderId,
                $eventId,
                $occurredAt,
                $traceId,
                $e->getMessage(),
            );
        })->dispatch();
    }
}
