<?php

namespace App\Console\Commands;

use App\Models\Notification;
use Illuminate\Console\Command;

class PruneNotifications extends Command
{
    protected $signature = 'notifications:prune';

    protected $description = 'Delete educator and student notifications at least 3 days old';

    public function handle(): int
    {
        $deleted = Notification::query()
            ->whereHas('recipient', fn ($query) => $query->whereIn('user_type', ['educator', 'student']))
            ->where('created_at', '<=', now()->subDays(3))
            ->delete();

        $this->info("Deleted {$deleted} notification(s).");

        return self::SUCCESS;
    }
}
