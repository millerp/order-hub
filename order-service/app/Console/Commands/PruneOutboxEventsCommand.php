<?php

namespace App\Console\Commands;

use App\Models\OutboxEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class PruneOutboxEventsCommand extends Command
{
    protected $signature = 'outbox:prune {--hours=72 : Keep published events newer than this amount of hours}';

    protected $description = 'Delete old published outbox events';

    public function handle(): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $threshold = Carbon::now()->subHours($hours);

        $deleted = OutboxEvent::query()
            ->whereNotNull('published_at')
            ->where('published_at', '<', $threshold)
            ->delete();

        $this->info("Pruned {$deleted} outbox event(s) older than {$hours} hour(s).");

        return self::SUCCESS;
    }
}
