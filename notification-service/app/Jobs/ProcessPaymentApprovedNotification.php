<?php

namespace App\Jobs;

use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessPaymentApprovedNotification implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [5, 30, 120];

    public int $uniqueFor = 3600;

    public function __construct(
        public readonly string $paymentId,
        public readonly string $orderId,
        public readonly string $eventId,
        public readonly ?string $occurredAt = null,
    ) {
        $this->onQueue('notification-emails');
    }

    public function uniqueId(): string
    {
        return $this->eventId;
    }

    public function tags(): array
    {
        return [
            'notification',
            'order:'.$this->orderId,
            'payment:'.$this->paymentId,
        ];
    }

    public function handle(): void
    {
        if (Notification::where('event_id', $this->eventId)->exists() || Notification::where('payment_id', $this->paymentId)->exists()) {
            return;
        }

        Log::info("Sending order confirmation email for Order ID: {$this->orderId}");

        Notification::create([
            'payment_id' => $this->paymentId,
            'order_id' => $this->orderId,
            'event_id' => $this->eventId,
            'occurred_at' => $this->occurredAt,
            'type' => 'email',
            'status' => 'sent',
        ]);
    }
}
