<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

// Task 01: failed enrollment-import rows handed back so the educator can see what to fix and
// retry. Mirrors FailedStudentRowsExport (Task 38).
class FailedEnrollmentRowsExport implements FromArray, WithHeadings
{
    public function __construct(private array $rows) {}

    public function array(): array
    {
        return array_map(fn ($r) => [
            $r['student_user_id'] ?? '', $r['subject_code'] ?? '', $r['section_name'] ?? '', $r['error'] ?? '',
        ], $this->rows);
    }

    public function headings(): array
    {
        return ['student_user_id', 'subject_code', 'section_name', 'reason'];
    }
}
