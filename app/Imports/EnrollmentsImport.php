<?php

namespace App\Imports;

use App\Models\Enrolled;
use App\Models\Subject;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

// G4: bulk enrollment import. Columns: student_user_id, subject_code, section_name, status.
// Dedupes within the file and against the DB; subjects must belong to the importing educator.
class EnrollmentsImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    private int $created = 0;
    private int $skipped = 0;
    private array $seen = []; // in-file dedupe keys

    public function __construct(private User $educator, private NotificationService $notifications) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $studentUserId = trim((string) ($row['student_user_id'] ?? ''));
            $code = trim((string) ($row['subject_code'] ?? ''));
            $sectionName = trim((string) ($row['section_name'] ?? ''));
            $active = filter_var($row['status'] ?? true, FILTER_VALIDATE_BOOLEAN);

            $student = User::where('user_id', $studentUserId)->where('user_type', 'student')->first();
            $subject = Subject::where('educator_id', $this->educator->id)
                ->where('subject_code', $code)
                ->whereHas('section', fn ($q) => $q->where('section_name', $sectionName))
                ->first();

            $key = $studentUserId.'|'.$code.'|'.$sectionName;
            if (! $student || ! $subject || isset($this->seen[$key])) {
                $this->skipped++;
                continue;
            }
            $this->seen[$key] = true;

            $rowModel = Enrolled::firstOrCreate(
                ['educator_id' => $this->educator->id, 'student_id' => $student->id, 'subject_id' => $subject->id],
                ['is_active' => $active],
            );

            if ($rowModel->wasRecentlyCreated) {
                $this->created++;
                $this->notifications->emit($this->educator, 'enrollment_created', $student->id, [
                    'subject_id' => $subject->id, 'title' => 'Enrolled in a new subject',
                ]);
            } else {
                $this->skipped++;
            }
        }
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function createdCount(): int
    {
        return $this->created;
    }

    public function skippedCount(): int
    {
        return $this->skipped;
    }
}
