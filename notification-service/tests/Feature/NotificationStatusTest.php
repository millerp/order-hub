<?php

namespace Tests\Feature;

use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_notification_record()
    {
        $notification = Notification::create([
            'payment_id' => 123,
            'order_id' => 456,
            'event_id' => 'd11d5532-8288-4fcb-a5a7-a496f992f4e5',
            'occurred_at' => now(),
            'type' => 'email',
            'status' => 'sent',
        ]);

        $this->assertDatabaseHas('notifications', [
            'payment_id' => 123,
            'order_id' => 456,
            'event_id' => 'd11d5532-8288-4fcb-a5a7-a496f992f4e5',
            'status' => 'sent',
        ]);
    }
}
