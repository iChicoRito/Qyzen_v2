<?php

namespace App\Jobs;

use App\Imports\StudentRowsImport;
use App\Models\SystemSetting;
use App\Models\UserImport;
use App\Services\StudentImportRowService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class DispatchStudentImport implements ShouldQueue
{
    use Queueable;

    public function __construct(public UserImport $userImport) {}

    public function handle(StudentImportRowService $rows): void
    {
        $import = $this->userImport->fresh();
        $import->forceFill([
            'status' => 'processing',
            'error_message' => null,
            'failed_rows' => [],
        ])->save();

        $sheet = new StudentRowsImport;
        Excel::import($sheet, Storage::disk('local')->path($import->upload_path));

        $normalizedRows = collect($sheet->rows)->map(fn (array $row) => $rows->normalize($row))->values();
        $chunks = $normalizedRows->chunk(StudentImportRowService::CHUNK_SIZE)->values();

        $import->forceFill([
            'total_rows' => $normalizedRows->count(),
            'total_chunks' => $chunks->count(),
        ])->save();

        if ($chunks->isEmpty()) {
            $import->forceFill(['status' => 'completed'])->save();
            Storage::disk('local')->delete($import->upload_path);

            return;
        }

        $this->dispatchChunks($import, $chunks, SystemSetting::offlineRegistrationEnabled());
    }

    public function dispatchChunks(UserImport $import, Collection $chunks, bool $offlineRegistration = false): void
    {
        foreach ($this->buildChunkJobs($import, $chunks, $offlineRegistration) as $job) {
            dispatch($job);
        }
    }

    public function buildChunkJobs(UserImport $import, Collection $chunks, bool $offlineRegistration = false): array
    {
        $jobs = [];

        foreach ($chunks as $index => $chunk) {
            $jobs[] = (new ProcessStudentImportChunk($import, $chunk->all(), $offlineRegistration))
                ->delay(now()->addSeconds($index * StudentImportRowService::CHUNK_DELAY_SECONDS));
        }

        return $jobs;
    }

    public function failed(?Throwable $exception): void
    {
        $this->userImport->forceFill([
            'status' => 'failed',
            'error_message' => $exception?->getMessage(),
        ])->save();
    }
}
