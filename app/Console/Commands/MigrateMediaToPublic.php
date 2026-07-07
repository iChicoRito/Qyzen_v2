<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MigrateMediaToPublic extends Command
{
    protected $signature = 'media:migrate-to-public';
    protected $description = 'Copy profile images from storage/app/public/ to public/ (one-time, symlink-free migration)';

    public function handle(): int
    {
        $src = storage_path('app/public/profile-media');
        $dst = public_path('profile-media');

        if (! File::isDirectory($src)) {
            $this->info('No profile-media directory found in storage — nothing to migrate.');
            return 0;
        }

        File::ensureDirectoryExists($dst);
        File::copyDirectory($src, $dst);

        $this->info('Copied ' . $src . ' → ' . $dst);
        $this->info('You may now delete storage/app/public/profile-media if desired.');
        return 0;
    }
}
