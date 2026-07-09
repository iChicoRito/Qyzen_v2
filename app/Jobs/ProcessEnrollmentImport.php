<?php

namespace App\Jobs;

use App\Exports\FailedEnrollmentRowsExport;
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
        Excel::import($import, Storage::disk('local')->path($record->upload_path));

        $failedRows = $import->failedRows();
        $reportPath = null;
        if ($failedRows) {
            $reportPath = 'imports/reports/enrollment-import-'.$record->id.'-failed.xlsx';
            Excel::store(new FailedEnrollmentRowsExport($failedRows), $reportPath, 'local');
        }

        $record->forceFill([
            'status' => 'completed',
            'created_count' => $import->createdCount(),
            'failed_rows' => $failedRows,
            'failed_report_path' => $reportPath,
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
