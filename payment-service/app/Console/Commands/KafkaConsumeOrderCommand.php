<?php

namespace App\Console\Commands;

use App\Models\Payment;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;

class KafkaConsumeOrderCommand extends Command
{
    protected $signature = 'kafka:consume-order';

    protected $description = 'Consume order events for payment processing';

    public function handle()
    {
        $this->info('Listening for order.created events...');

        $consumer = Kafka::consumer(['order.created'], 'payment-service-group')
            ->withHandler(function (ConsumerMessage $message) {
                $payload = $message->getBody();
                $orderId = $payload['order_id'];
                $amount = $payload['amount'];
                $traceId = (string) ($payload['trace_id'] ?? ($message->getHeaders()['x-trace-id'] ?? Str::uuid()->toString()));

                if (Payment::where('order_id', $orderId)->exists()) {
                    $this->info("Order $orderId already processed. Skipping.");

                    return;
                }

                try {
                    if (rand(1, 100) <= 10) {
                        throw new \Exception('Simulated technical failure');
                    }

                    $status = (rand(1, 100) <= 80) ? 'approved' : 'failed';

                    $payment = Payment::create([
                        'order_id' => $orderId,
                        'amount' => $amount,
                        'status' => $status,
                        'trace_id' => $traceId,
                    ]);

                    $topicName = $status === 'approved' ? 'payment.approved' : 'payment.failed';
                    $this->info("Publishing payment event to topic: $topicName");
                    $eventMessage = (new Message(
                        body: [
                            'order_id' => $orderId,
                            'payment_id' => (string) $payment->id,
                            'status' => $status,
                            'event_id' => (string) Str::uuid(),
                            'occurred_at' => now()->toIso8601String(),
                            'trace_id' => $traceId,
                        ]
                    ))->withHeader('x-trace-id', $traceId);

                    Kafka::publish()->onTopic($topicName)
                        ->withMessage($eventMessage)
                        ->send();

                    $this->info("Processed payment for Order $orderId: $status trace_id=$traceId");

                } catch (\Exception $e) {
                    $this->error("Error processing order $orderId: ".$e->getMessage());

                    $dlqMessage = (new Message(
                        body: [
                            'original_message' => $payload,
                            'error' => $e->getMessage(),
                            'trace_id' => $traceId,
                        ]
                    ))->withHeader('x-trace-id', $traceId);

                    Kafka::publish()->onTopic('payment.failed.dlq')
                        ->withMessage($dlqMessage)
                        ->send();
                }
            })
            ->build();

        $consumer->consume();
    }
}
