<?php

namespace Tests\Feature;

use App\Domain\PaymentApprovedPayload;
use App\Events\PaymentApprovedReceived;
use App\Jobs\FinalizeNotificationDelivery;
use App\Jobs\ProcessPaymentApprovedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class QueuePaymentApprovedNotificationListenerTest extends TestCase
{
    use RefreshDatabase;

    public function test_listener_queues_notification_job_from_event(): void
    {
        Bus::fake();

        event(new PaymentApprovedReceived(
            new PaymentApprovedPayload(
                paymentId: '120',
                orderId: '220',
                eventId: 'evt-listener-1',
                occurredAt: now()->toIso8601String(),
                traceId: 'trace-abc',
            )
        ));

        Bus::assertChained([
            function (ProcessPaymentApprovedNotification $job) {
                return $job->paymentId === '120'
                    && $job->orderId === '220'
                    && $job->eventId === 'evt-listener-1'
                    && $job->traceId === 'trace-abc';
            },
            FinalizeNotificationDelivery::class,
        ]);
    }
}
