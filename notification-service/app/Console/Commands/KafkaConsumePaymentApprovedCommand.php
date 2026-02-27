<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Notification;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Contracts\ConsumerMessage;
use Illuminate\Support\Facades\Log;

class KafkaConsumePaymentApprovedCommand extends Command
{
    protected $signature = 'kafka:consume-payment-approved';
    protected $description = 'Consume payment.approved events for notifications';

    public function handle(): void
    {
        $this->info("Listening for payment.approved events...");

        $consumer = Kafka::consumer(['payment.approved'], 'notification-service-group')
            ->withHandler(function (ConsumerMessage $message) {
                $payload = $message->getBody();
                $paymentId = $payload['payment_id'];
                $orderId = $payload['order_id'];

                if (Notification::where('payment_id', $paymentId)->exists()) {
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
                        'type' => 'email',
                        'status' => 'sent'
                    ]);

                    $this->info("Email sent for Order $orderId");

                } catch (\Exception $e) {
                    Log::error("Failed to send notification for payment $paymentId: " . $e->getMessage());
                }
            })
            ->build();

        $consumer->consume();
    }
}
