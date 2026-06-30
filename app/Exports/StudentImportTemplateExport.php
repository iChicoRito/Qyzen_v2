<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

// F3: headers-only template for bulk student import.
class StudentImportTemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        // One example row to show the expected format.
        return [['2026-00001', 'Juan', 'Dela Cruz', 'juan.delacruz@example.com', 'active']];
    }

    public function headings(): array
    {
        return ['user_id', 'given_name', 'surname', 'email', 'status'];
    }
}
