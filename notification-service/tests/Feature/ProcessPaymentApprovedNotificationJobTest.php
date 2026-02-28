<?php

namespace Tests\Feature;

use App\Jobs\ProcessPaymentApprovedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessPaymentApprovedNotificationJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_creates_notification_record(): void
    {
        $job = new ProcessPaymentApprovedNotification(
            paymentId: '101',
            orderId: '501',
            eventId: 'event-101',
            occurredAt: now()->toIso8601String(),
        );

        $job->handle();

        $this->assertDatabaseHas('notifications', [
            'payment_id' => '101',
            'order_id' => '501',
            'event_id' => 'event-101',
            'status' => 'sent',
        ]);
    }

    public function test_job_is_idempotent_for_same_event(): void
    {
        $job = new ProcessPaymentApprovedNotification(
            paymentId: '102',
            orderId: '502',
            eventId: 'event-102',
            occurredAt: now()->toIso8601String(),
        );

        $job->handle();
        $job->handle();

        $this->assertDatabaseCount('notifications', 1);
    }
}
