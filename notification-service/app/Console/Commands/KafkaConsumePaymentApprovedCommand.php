<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Notification;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;
use Illuminate\Support\Facades\Log;

class KafkaConsumePaymentApprovedCommand extends Command
{
    protected $signature = 'kafka:consume-payment-approved';
    protected $description = 'Consume payment.approved events for notifications';

    public function handle()
    {
        $conf = new Conf();
        $conf->set('group.id', 'notification-service-group');
        $conf->set('metadata.broker.list', env('KAFKA_BROKERS', 'kafka:9092'));
        $conf->set('auto.offset.reset', 'earliest');
        $conf->set('enable.auto.commit', 'false');

        $consumer = new KafkaConsumer($conf);
        $consumer->subscribe(['payment.approved']);

        $this->info("Listening for payment.approved events...");

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
                    $paymentId = $payload['payment_id'];
                    $orderId = $payload['order_id'];
                    
                    if (Notification::where('payment_id', $paymentId)->exists()) {
                        $this->info("Notification for payment $paymentId already sent. Skipping.");
                        $consumer->commit($message);
                        continue 2;
                    }

                    try {
                        // Simulate sending email
                        Log::info("Sending order confirmation email for Order ID: $orderId");
                        
                        Notification::create([
                            'payment_id' => $paymentId,
                            'order_id' => $orderId,
                            'type' => 'email',
                            'status' => 'sent'
                        ]);
                        
                        $consumer->commit($message);
                        $this->info("Email sent for Order $orderId");

                    } catch (\Exception $e) {
                        Log::error("Failed to send notification for payment $paymentId: " . $e->getMessage());
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
