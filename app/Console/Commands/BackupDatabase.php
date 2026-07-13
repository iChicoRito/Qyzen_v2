<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

// Task 05 (task 2): streams DatabaseBackupService::lines() straight to the database-backups disk,
// never builds the dump as one in-memory string, then prunes to
// the 7 most recent backups so unattended daily cron runs on Hostinger's small disk quota can't
// fill it. Scheduled from routes/console.php; see that file's comment for the required Hostinger
// hPanel Cron Jobs entry (this command only runs on request/schedule, it can't wire the panel).
class BackupDatabase extends Command
{
    protected $signature = 'backup:database';

    protected $description = 'Export a full SQL database backup to durable private storage and keep only the 7 most recent';

    private const KEEP = 7;

    // Anchors both filename generation and pruning's filter, so pruneOldBackups() only ever
    // touches files this command wrote — an unrelated file dropped into backups/ is never swept
    // into the mtime ranking and can't be silently deleted once it ages out of the top 7. The
    // trailing 20-digit group is a zero-padded hrtime(true) nanosecond counter, appended purely
    // as a same-second tiebreaker: hrtime(true) is CLOCK_MONOTONIC, which resets to ~0 on every
    // reboot, so it is never sorted on its own (that would rank pre-reboot backups above
    // genuinely newer post-reboot ones). Pruning sorts on the full filename instead — its
    // leading Y-m-d_His component is wall-clock and always increases across reboots, and the
    // trailing sequence only breaks ties within the same second.
    private const NAME_PATTERN = '/^qyzen-backup-\d{4}-\d{2}-\d{2}_\d{6}-(\d{20})\.sql$/';

    public function handle(DatabaseBackupService $backups): int
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('database-backups');

        $sequence = sprintf('%020d', hrtime(true));
        $path = 'qyzen-backup-'.now()->format('Y-m-d_His')."-{$sequence}.sql";

        $handle = fopen($disk->path($path), 'w');

        if ($handle === false) {
            $this->error("Could not open {$path} for writing.");

            return self::FAILURE;
        }

        try {
            foreach ($backups->lines() as $chunk) {
                if (fwrite($handle, $chunk) !== strlen($chunk)) {
                    throw new RuntimeException("Could not write the complete backup to {$path}.");
                }
            }
        } catch (Throwable $e) {
            // Don't leave a partial/corrupt dump on disk — it would otherwise be counted as a
            // real backup (and occupy/evict a retention slot) on the next successful run.
            fclose($handle);
            $disk->delete($path);

            throw $e;
        }

        fclose($handle);

        $this->info("Backup written to the database-backups disk: {$path}");

        $this->pruneOldBackups($disk);

        return self::SUCCESS;
    }

    private function pruneOldBackups(FilesystemAdapter $disk): void
    {
        $files = collect($disk->files())
            ->filter(fn (string $file) => preg_match(self::NAME_PATTERN, basename($file)) === 1)
            ->sortByDesc(fn (string $file) => basename($file), SORT_STRING)
            ->values();

        foreach ($files->slice(self::KEEP) as $stale) {
            $disk->delete($stale);
        }
    }
}
