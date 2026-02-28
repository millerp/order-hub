<?php

namespace App\Console\Commands;

use App\Domain\PaymentApprovedPayload;
use App\Events\PaymentApprovedReceived;
use App\Models\Notification;
use Illuminate\Console\Command;
use InvalidArgumentException;
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
                try {
                    $contract = PaymentApprovedPayload::fromArray($payload);
                } catch (InvalidArgumentException $e) {
                    $this->warn($e->getMessage());

                    return;
                }
                $traceId = (string) ($contract->traceId ?: ($message->getHeaders()['x-trace-id'] ?? ''));

                $paymentId = $contract->paymentId;
                $orderId = $contract->orderId;
                $eventId = $contract->eventId;

                if (Notification::where('event_id', $eventId)->exists() || Notification::where('payment_id', $paymentId)->exists()) {
                    $this->info("Notification for payment $paymentId already sent. Skipping.");

                    return;
                }

                event(new PaymentApprovedReceived($contract));
                $this->info("PaymentApprovedReceived event dispatched for Order $orderId trace_id=$traceId");
            })
            ->build();

        $consumer->consume();
    }
}
