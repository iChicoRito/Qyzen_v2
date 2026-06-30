<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

// F3: failed import rows handed back so the admin can fix and retry.
class FailedStudentRowsExport implements FromArray, WithHeadings
{
    public function __construct(private array $rows) {}

    public function array(): array
    {
        return array_map(fn ($r) => [
            $r['user_id'], $r['given_name'], $r['surname'], $r['email'],
            $r['is_active'] ? 'active' : 'inactive', $r['error'] ?? '',
        ], $this->rows);
    }

    public function headings(): array
    {
        return ['user_id', 'given_name', 'surname', 'email', 'status', 'error'];
    }
}
