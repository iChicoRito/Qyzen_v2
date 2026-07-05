<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

// Task 38: failed import rows handed back so the admin can see what to fix and retry.
// The reason already carries the "Row N:" prefix from ProcessStudentImportChunk.
class FailedStudentRowsExport implements FromArray, WithHeadings
{
    public function __construct(private array $rows) {}

    public function array(): array
    {
        return array_map(fn ($r) => [
            $r['user_id'] ?? '', $r['email'] ?? '', $r['error'] ?? '',
        ], $this->rows);
    }

    public function headings(): array
    {
        return ['user_id', 'email', 'reason'];
    }
}
