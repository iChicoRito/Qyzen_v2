<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ScoreUploadTemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [
            ['2026-12345', 8],
        ];
    }

    public function headings(): array
    {
        return ['student_id', 'score'];
    }
}
