<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Payment;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;

class KafkaConsumeOrderCommand extends Command
{
    protected $signature = 'kafka:consume-order';
    protected $description = 'Consume order events for payment processing';

    public function handle()
    {
        $conf = new Conf();
        $conf->set('group.id', 'payment-service-group');
        $conf->set('metadata.broker.list', env('KAFKA_BROKERS', 'kafka:9092'));
        $conf->set('auto.offset.reset', 'earliest');
        $conf->set('enable.auto.commit', 'false'); // Manual commit

        $consumer = new KafkaConsumer($conf);
        $consumer->subscribe(['order.created']);

        $producerConf = new Conf();
        $producerConf->set('metadata.broker.list', env('KAFKA_BROKERS', 'kafka:9092'));
        $producer = new \RdKafka\Producer($producerConf);

        $this->info("Listening for order.created events...");

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
                    $orderId = $payload['order_id'];
                    $amount = $payload['amount'];
                    
                    if (Payment::where('order_id', $orderId)->exists()) {
                        $this->info("Order $orderId already processed. Skipping.");
                        $consumer->commit($message);
                        continue 2;
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
                        $topic = $producer->newTopic($topicName);
                        
                        $eventPayload = json_encode([
                            'order_id' => $orderId,
                            'payment_id' => uniqid(),
                            'status' => $status
                        ]);
                        
                        $topic->produce(RD_KAFKA_PARTITION_UA, 0, $eventPayload);
                        $producer->poll(0);
                        
                        $consumer->commit($message);
                        $this->info("Processed payment for Order $orderId: $status");

                    } catch (\Exception $e) {
                        $this->error("Error processing order $orderId: " . $e->getMessage());
                        
                        $dlqTopic = $producer->newTopic('payment.failed.dlq');
                        $dlqTopic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode([
                            'original_message' => $payload,
                            'error' => $e->getMessage()
                        ]));
                        $producer->poll(0);
                        
                        $consumer->commit($message);
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
