<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

// G4: headers-only template for bulk enrollment import.
class EnrollmentImportTemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [['2026-00001', 'MATH101', 'Section A', 'active']];
    }

    public function headings(): array
    {
        return ['student_user_id', 'subject_code', 'section_name', 'status'];
    }
}
