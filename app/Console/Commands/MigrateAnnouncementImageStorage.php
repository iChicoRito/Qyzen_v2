<?php

namespace App\Console\Commands;

use App\Models\Announcement;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

#[Signature('announcements:migrate-image-storage')]
#[Description('Copy legacy announcement images from local storage to durable storage')]
class MigrateAnnouncementImageStorage extends Command
{
    public function handle(): int
    {
        $copied = 0;
        $existing = 0;
        $missing = 0;
        $target = Storage::disk(Announcement::PRIVATE_DISK);
        $legacy = Storage::disk('local');

        Announcement::whereNotNull('images')->orderBy('id')->each(
            function (Announcement $announcement) use ($target, $legacy, &$copied, &$existing, &$missing): void {
                foreach ($announcement->images ?? [] as $image) {
                    $path = is_array($image) ? ($image['path'] ?? null) : null;
                    if (! is_string($path) || $path === '') {
                        continue;
                    }

                    if ($target->exists($path)) {
                        $existing++;

                        continue;
                    }

                    if (! $legacy->exists($path)) {
                        $missing++;

                        continue;
                    }

                    if (! $target->put($path, $legacy->get($path))) {
                        throw new RuntimeException("Could not copy announcement image [{$path}].");
                    }

                    $copied++;
                }
            }
        );

        $this->info("Copied {$copied} image(s); {$existing} already present; {$missing} missing.");

        return self::SUCCESS;
    }
}
