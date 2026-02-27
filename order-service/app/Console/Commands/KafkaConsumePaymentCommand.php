<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Facades\Kafka;

class KafkaConsumePaymentCommand extends Command
{
    protected $signature = 'kafka:consume-payment';

    protected $description = 'Consume payment events to update order status';

    public function handle(): void
    {
        $this->info('Listening for payment events...');

        $consumer = Kafka::consumer(['payment.approved', 'payment.failed'], 'order-service-group')
            ->withHandler(function (ConsumerMessage $message) {
                $payload = $message->getBody();
                if (! isset($payload['order_id'], $payload['event_id'])) {
                    $this->warn('Skipping payment event with invalid payload.');

                    return;
                }

                $order = Order::find($payload['order_id']);
                if (! $order) {
                    $this->warn("Order {$payload['order_id']} not found for payment event {$payload['event_id']}.");

                    return;
                }

                if (in_array($order->status, ['paid', 'cancelled'], true)) {
                    $this->info("Order {$order->id} already finalized as {$order->status}. Skipping duplicate event.");

                    return;
                }

                $topic = $message->getTopicName();
                if ($topic === 'payment.approved') {
                    $order->status = 'paid';
                    $this->info("Order {$order->id} marked as paid.");
                } else {
                    $order->status = 'cancelled';
                    $this->info("Order {$order->id} marked as cancelled.");
                }
                $order->save();
            })
            ->build();

        $consumer->consume();
    }
}
