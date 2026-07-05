<?php

namespace App\Jobs;

use App\Exceptions\EnrollmentRowException;
use App\Imports\EnrollmentsImport;
use App\Models\EnrollmentImport;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ProcessEnrollmentImport implements ShouldQueue
{
    use Queueable;

    public function __construct(public EnrollmentImport $enrollmentImport) {}

    public function handle(NotificationService $notifications): void
    {
        $record = $this->enrollmentImport->fresh(['owner']);
        $record->forceFill([
            'status' => 'processing',
            'error_message' => null,
        ])->save();

        $import = new EnrollmentsImport($record->owner, $notifications);

        try {
            Excel::import($import, Storage::disk('local')->path($record->upload_path));
        } catch (EnrollmentRowException $e) {
            $record->forceFill([
                'status' => 'failed',
                'error_message' => "Row {$e->row} is invalid.",
            ])->save();

            return;
        }

        $record->forceFill([
            'status' => 'completed',
            'created_count' => $import->createdCount(),
        ])->save();

        Storage::disk('local')->delete($record->upload_path);
    }

    public function failed(?Throwable $exception): void
    {
        $this->enrollmentImport->forceFill([
            'status' => 'failed',
            'error_message' => $exception?->getMessage(),
        ])->save();
    }
}
