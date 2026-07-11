<?php

namespace App\Console\Commands;

use App\Models\LearningMaterial;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('materials:migrate-storage')]
#[Description('Copy legacy learning materials from local storage to durable storage')]
class MigrateLearningMaterialStorage extends Command
{
    public function handle(): int
    {
        $copied = 0;
        $missing = 0;
        $target = Storage::disk(LearningMaterial::PRIVATE_DISK);
        $legacy = Storage::disk('local');

        LearningMaterial::where('storage_bucket', '!=', LearningMaterial::PRIVATE_DISK)
            ->orderBy('id')
            ->each(function (LearningMaterial $material) use ($target, $legacy, &$copied, &$missing): void {
                if (! $legacy->exists($material->storage_path)) {
                    $missing++;

                    return;
                }

                if (! $target->exists($material->storage_path)) {
                    $target->put($material->storage_path, $legacy->get($material->storage_path));
                }

                $material->update(['storage_bucket' => LearningMaterial::PRIVATE_DISK]);
                $copied++;
            });

        $this->info("Copied {$copied} material file(s); {$missing} missing.");

        return self::SUCCESS;
    }
}
