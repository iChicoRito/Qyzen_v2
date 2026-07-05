<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

// Task 38: positional reader for the student upload template. Reads only the first worksheet
// (default for a single-sheet ToCollection import), maps columns A-E by position (header text is
// ignored), skips the title (row 1) + header (row 2) and any fully-blank row, and tags each kept
// row with its 1-based spreadsheet row number for error reporting (data starts at row 3).
class StudentRowsImport implements ToCollection
{
    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $number = $index + 1;

            if ($number <= 2) {
                continue; // skip title (1) + header (2)
            }

            $cells = $row->values()->all();
            $cell = fn (int $i) => trim((string) ($cells[$i] ?? ''));

            $mapped = [
                'user_id' => $cell(0),
                'given_name' => $cell(1),
                'surname' => $cell(2),
                'email' => $cell(3),
                'role_names' => $cell(4),
            ];

            if ($mapped['user_id'] === '' && $mapped['given_name'] === '' && $mapped['surname'] === ''
                && $mapped['email'] === '' && $mapped['role_names'] === '') {
                continue; // fully blank row
            }

            $this->rows[] = $mapped + ['_row' => $number];
        }
    }
}
