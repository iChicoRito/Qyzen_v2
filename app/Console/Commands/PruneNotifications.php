<?php

namespace App\Console\Commands;

use App\Models\Notification;
use Illuminate\Console\Command;

class PruneNotifications extends Command
{
    protected $signature = 'notifications:prune';

    protected $description = 'Delete educator read notifications older than 3 days';

    public function handle(): int
    {
        $deleted = Notification::query()
            ->whereHas('recipient', fn ($query) => $query->where('user_type', 'educator'))
            ->where('is_read', true)
            ->where('created_at', '<', now()->subDays(3))
            ->delete();

        $this->info("Deleted {$deleted} notification(s).");

        return self::SUCCESS;
    }
}
