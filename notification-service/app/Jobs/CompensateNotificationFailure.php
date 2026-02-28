<?php

namespace App\Jobs;

use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CompensateNotificationFailure implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $paymentId,
        public readonly string $orderId,
        public readonly string $eventId,
        public readonly ?string $occurredAt = null,
        public readonly ?string $traceId = null,
        public readonly string $errorMessage = 'Notification workflow failed',
    ) {
        $this->onQueue('notification-emails');
    }

    public function handle(): void
    {
        Notification::updateOrCreate(
            ['event_id' => $this->eventId],
            [
                'payment_id' => $this->paymentId,
                'order_id' => $this->orderId,
                'occurred_at' => $this->occurredAt,
                'trace_id' => $this->traceId,
                'type' => 'email',
                'status' => 'failed',
                'error_message' => $this->errorMessage,
            ]
        );
    }
}
