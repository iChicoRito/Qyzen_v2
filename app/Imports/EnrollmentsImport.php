<?php

namespace App\Imports;

use App\Models\Enrolled;
use App\Models\Subject;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

// Task 39/01: bulk enrollment import. Columns read POSITIONALLY (A-D): student_user_id,
// subject_code, section_name, status — header text is ignored. Reads only the first
// worksheet, skips fully blank rows. Invalid rows are collected (not fatal) so the rest of
// the file still imports; the educator gets a downloadable report of what failed and why.
// Subjects/sections must belong to the importing educator; students matched case-insensitively.
class EnrollmentsImport implements ToCollection
{
    private int $created = 0;

    private array $failed = [];

    private bool $processed = false; // first worksheet only

    public function __construct(private User $educator, private NotificationService $notifications) {}

    public function collection(Collection $rows): void
    {
        if ($this->processed) {
            return; // ignore any additional sheets
        }
        $this->processed = true;

        foreach ($rows as $index => $row) {
            $number = $index + 1;
            if ($number <= 2) {
                continue; // skip title (row 1) + header (row 2)
            }

            $cells = $row->values()->all();
            $cell = fn (int $i) => trim((string) ($cells[$i] ?? ''));

            $studentUserId = $cell(0);
            $code = $cell(1);
            $sectionName = $cell(2);
            $status = strtolower($cell(3));

            if ($studentUserId === '' && $code === '' && $sectionName === '' && $status === '') {
                continue; // fully blank row
            }

            $student = User::where('user_type', 'student')
                ->whereRaw('LOWER(user_id) = ?', [strtolower($studentUserId)])
                ->first();

            $subject = Subject::where('educator_id', $this->educator->id)
                ->where('subject_code', $code)
                ->whereHas('section', fn ($q) => $q->where('section_name', $sectionName))
                ->first();

            if (! $student || ! $subject || ! in_array($status, ['active', 'inactive'], true)) {
                $reason = ! $student ? 'Unknown student_user_id.'
                    : (! $subject ? 'Unknown subject_code/section_name for this educator.' : 'status must be active or inactive.');
                $this->failed[] = [
                    'student_user_id' => $studentUserId, 'subject_code' => $code, 'section_name' => $sectionName,
                    'error' => "Row {$number}: {$reason}",
                ];

                continue;
            }

            $rowModel = Enrolled::firstOrCreate(
                ['educator_id' => $this->educator->id, 'student_id' => $student->id, 'subject_id' => $subject->id],
                ['is_active' => $status === 'active'],
            );

            if ($rowModel->wasRecentlyCreated) {
                $this->created++;
                $this->notifications->emit($this->educator, 'enrollment_created', $student->id, [
                    'subject_id' => $subject->id, 'title' => 'Enrolled in a new subject',
                    'link_path' => route('student.assessments.index'),
                ]);
            }
        }
    }

    public function createdCount(): int
    {
        return $this->created;
    }

    public function failedRows(): array
    {
        return $this->failed;
    }
}
