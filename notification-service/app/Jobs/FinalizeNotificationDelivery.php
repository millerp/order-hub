<?php

namespace App\Jobs;

use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FinalizeNotificationDelivery implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $eventId,
    ) {
        $this->onQueue('notification-emails');
    }

    public function handle(): void
    {
        Notification::query()
            ->where('event_id', $this->eventId)
            ->update([
                'status' => 'sent',
                'error_message' => null,
            ]);
    }
}
