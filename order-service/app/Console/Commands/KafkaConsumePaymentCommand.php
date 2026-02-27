<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;

class KafkaConsumePaymentCommand extends Command
{
    protected $signature = 'kafka:consume-payment';
    protected $description = 'Consume payment events to update order status';

    public function handle()
    {
        $conf = new Conf();
        $conf->set('group.id', 'order-service-group');
        $conf->set('metadata.broker.list', env('KAFKA_BROKERS', 'kafka:9092'));
        $conf->set('auto.offset.reset', 'earliest');

        $consumer = new KafkaConsumer($conf);
        $consumer->subscribe(['payment.approved', 'payment.failed']);

        $this->info("Listening for payment events...");

        $run = true;
        $this->trap([SIGINT, SIGTERM], function () use (&$run) {
            $this->warn('Received shutdown signal. Closing consumer gracefully...');
            $run = false;
        });

        while ($run) {
            $message = $consumer->consume(2000);
            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    $payload = json_decode($message->payload, true);
                    $order = Order::find($payload['order_id']);
                    if ($order) {
                        $topic = $message->topic_name;
                        if ($topic === 'payment.approved') {
                            $order->status = 'paid';
                            $this->info("Order {$order->id} marked as paid. ");
                        } else {
                            $order->status = 'cancelled';
                            $this->info("Order {$order->id} marked as cancelled.");
                        }
                        $order->save();
                    }
                    break;
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    break;
                default:
                    $this->error($message->errstr());
                    break;
            }
        }
    }
}
