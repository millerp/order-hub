<?php

namespace App\Console\Commands;

use App\Models\OutboxEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;

class KafkaPublishOutboxCommand extends Command
{
    protected $signature = 'kafka:publish-outbox {--once : Publish one batch and stop}';

    protected $description = 'Publish pending outbox events to Kafka';

    public function handle(): int
    {
        do {
            $processed = $this->publishBatch();

            if ($this->option('once')) {
                break;
            }

            if ($processed === 0) {
                sleep(1);
            }
        } while (true);

        return self::SUCCESS;
    }

    private function publishBatch(): int
    {
        $events = OutboxEvent::query()
            ->whereNull('published_at')
            ->orderBy('id')
            ->limit(100)
            ->get();

        foreach ($events as $event) {
            try {
                Kafka::publish()
                    ->onTopic($event->event_type)
                    ->withMessage((new Message(body: $event->payload))
                        ->withHeader('x-trace-id', (string) ($event->payload['trace_id'] ?? ''))
                        ->withHeader('traceparent', (string) ($event->payload['traceparent'] ?? '')))
                    ->send();

                $event->update([
                    'published_at' => Carbon::now(),
                    'last_error' => null,
                ]);
            } catch (\Throwable $e) {
                $event->increment('attempts');
                $event->update(['last_error' => $e->getMessage()]);
                Log::error('Outbox publish failed', [
                    'event_id' => $event->id,
                    'event_type' => $event->event_type,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $events->count();
    }
}
