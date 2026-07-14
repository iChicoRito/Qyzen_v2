<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class OfflineScoresImport implements ToCollection, WithHeadingRow
{
    public array $rows = [];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $values = is_array($row) ? $row : $row->toArray();
            $empty = collect(['student_id', 'score'])
                ->every(fn ($key) => trim((string) ($values[$key] ?? '')) === '');

            if ($empty) {
                continue;
            }

            $this->rows[] = $values + ['_row' => $index + 2];
        }
    }
}
