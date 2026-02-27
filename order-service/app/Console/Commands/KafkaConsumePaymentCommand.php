<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Contracts\ConsumerMessage;

class KafkaConsumePaymentCommand extends Command
{
    protected $signature = 'kafka:consume-payment';
    protected $description = 'Consume payment events to update order status';

    public function handle()
    {
        $this->info("Listening for payment events...");

        $consumer = Kafka::consumer(['payment.approved', 'payment.failed'], 'order-service-group')
            ->withHandler(function (ConsumerMessage $message) {
                $payload = $message->getBody();
                $order = Order::find($payload['order_id']);

                if ($order) {
                    $topic = $message->getTopicName();
                    if ($topic === 'payment.approved') {
                        $order->status = 'paid';
                        $this->info("Order {$order->id} marked as paid.");
                    } else {
                        $order->status = 'cancelled';
                        $this->info("Order {$order->id} marked as cancelled.");
                    }
                    $order->save();
                }
            })
            ->build();

        $consumer->consume();
    }
}
