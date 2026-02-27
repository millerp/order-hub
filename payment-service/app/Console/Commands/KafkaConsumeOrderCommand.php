<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Payment;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Message\Message;

class KafkaConsumeOrderCommand extends Command
{
    protected $signature = 'kafka:consume-order';
    protected $description = 'Consume order events for payment processing';

    public function handle()
    {
        $this->info("Listening for order.created events...");

        $consumer = Kafka::consumer(['order.created'], 'payment-service-group')
            ->withHandler(function (ConsumerMessage $message) {
                $payload = $message->getBody();
                $orderId = $payload['order_id'];
                $amount = $payload['amount'];

                if (Payment::where('order_id', $orderId)->exists()) {
                    $this->info("Order $orderId already processed. Skipping.");
                    return;
                }

                try {
                    if (rand(1, 100) <= 10) {
                        throw new \Exception("Simulated technical failure");
                    }

                    $status = (rand(1, 100) <= 80) ? 'approved' : 'failed';

                    Payment::create([
                        'order_id' => $orderId,
                        'amount' => $amount,
                        'status' => $status
                    ]);

                    $topicName = $status === 'approved' ? 'payment.approved' : 'payment.failed';
                    $this->info("Publishing payment event to topic: $topicName");
                    Kafka::publish()->onTopic($topicName)
                        ->withMessage(new Message(
                            body: [
                                'order_id' => $orderId,
                                'payment_id' => uniqid(),
                                'status' => $status
                            ]
                        ))
                        ->send();

                    $this->info("Processed payment for Order $orderId: $status");

                } catch (\Exception $e) {
                    $this->error("Error processing order $orderId: " . $e->getMessage());

                    Kafka::publish()->onTopic('payment.failed.dlq')
                        ->withMessage(new Message(
                            body: [
                                'original_message' => $payload,
                                'error' => $e->getMessage()
                            ]
                        ))
                        ->send();
                }
            })
            ->build();

        $consumer->consume();
    }
}
