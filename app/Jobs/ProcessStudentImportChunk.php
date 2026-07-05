<?php

namespace App\Jobs;

use App\Exports\FailedStudentRowsExport;
use App\Models\UserImport;
use App\Services\StudentImportRowService;
use App\Services\UserOnboardingService;
use App\Services\UserService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ProcessStudentImportChunk implements ShouldQueue
{
    use Queueable;

    public function __construct(public UserImport $userImport, public array $rows) {}

    public function handle(
        StudentImportRowService $rowService,
        UserService $users,
        UserOnboardingService $onboarding,
    ): void {
        $created = 0;
        $failed = [];

        foreach ($this->rows as $row) {
            $validator = $rowService->validate($row);

            if ($validator->fails()) {
                $failed[] = $row + ['error' => 'Row '.$row['_row'].': '.$validator->errors()->first()];

                continue;
            }

            if ($row['user_id'] === '') {
                $row['user_id'] = $rowService->placeholderUserId();
            }

            $user = $users->create($row, $row['role_names']);
            $onboarding->send($user);
            $created++;
        }

        DB::transaction(function () use ($created, $failed) {
            /** @var UserImport $import */
            $import = UserImport::query()->lockForUpdate()->findOrFail($this->userImport->id);
            $failedRows = $import->failed_rows ?? [];
            $failedRows = array_merge($failedRows, $failed);

            $import->created_count += $created;
            $import->failed_count += count($failed);
            $import->processed_chunks += 1;
            $import->failed_rows = $failedRows;

            if ($import->processed_chunks >= $import->total_chunks) {
                $import->status = 'completed';

                if (! empty($failedRows)) {
                    $reportPath = 'imports/reports/user-import-'.$import->id.'-failed.xlsx';
                    Excel::store(new FailedStudentRowsExport($failedRows), $reportPath, 'local');
                    $import->failed_report_path = $reportPath;
                }
            }

            $import->save();
        });

        $import = $this->userImport->fresh();
        if ($import->status === 'completed') {
            Storage::disk('local')->delete($import->upload_path);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->userImport->forceFill([
            'status' => 'failed',
            'error_message' => $exception?->getMessage(),
        ])->save();
    }
}
