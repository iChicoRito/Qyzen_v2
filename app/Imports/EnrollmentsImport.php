<?php

namespace App\Imports;

use App\Exceptions\EnrollmentRowException;
use App\Models\Enrolled;
use App\Models\Subject;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

// Task 39: bulk enrollment import. Columns read POSITIONALLY (A-D): student_user_id,
// subject_code, section_name, status — header text is ignored. Reads only the first
// worksheet, skips fully blank rows, and HALTS at the first invalid row (throws
// EnrollmentRowException with the spreadsheet row number). Subjects/sections must belong
// to the importing educator; students matched case-insensitively.
class EnrollmentsImport implements ToCollection
{
    private int $created = 0;

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
                throw new EnrollmentRowException($number);
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
}
