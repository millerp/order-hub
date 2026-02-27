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
            'type' => 'email',
            'status' => 'sent'
        ]);

        $this->assertDatabaseHas('notifications', [
            'payment_id' => 123,
            'order_id' => 456,
            'status' => 'sent'
        ]);
    }
}
