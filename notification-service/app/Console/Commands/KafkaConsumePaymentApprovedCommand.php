<?php

namespace App\Console\Commands;

use App\Models\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Facades\Kafka;

class KafkaConsumePaymentApprovedCommand extends Command
{
    protected $signature = 'kafka:consume-payment-approved';

    protected $description = 'Consume payment.approved events for notifications';

    public function handle(): void
    {
        $this->info('Listening for payment.approved events...');

        $consumer = Kafka::consumer(['payment.approved'], 'notification-service-group')
            ->withHandler(function (ConsumerMessage $message) {
                $payload = $message->getBody();
                if (! isset($payload['payment_id'], $payload['order_id'], $payload['event_id'])) {
                    $this->warn('Skipping payment.approved event with invalid payload.');

                    return;
                }

                $paymentId = $payload['payment_id'];
                $orderId = $payload['order_id'];
                $eventId = $payload['event_id'];
                $occurredAt = $payload['occurred_at'] ?? null;

                if (Notification::where('event_id', $eventId)->exists() || Notification::where('payment_id', $paymentId)->exists()) {
                    $this->info("Notification for payment $paymentId already sent. Skipping.");

                    return;
                }

                try {
                    // Simulate sending email
                    Log::info("Sending order confirmation email for Order ID: $orderId");
                    $this->info("Sending order confirmation email for Order ID: $orderId");

                    Notification::create([
                        'payment_id' => $paymentId,
                        'order_id' => $orderId,
                        'event_id' => $eventId,
                        'occurred_at' => $occurredAt,
                        'type' => 'email',
                        'status' => 'sent',
                    ]);

                    $this->info("Email sent for Order $orderId");

                } catch (\Exception $e) {
                    Log::error("Failed to send notification for payment $paymentId: ".$e->getMessage());
                }
            })
            ->build();

        $consumer->consume();
    }
}
