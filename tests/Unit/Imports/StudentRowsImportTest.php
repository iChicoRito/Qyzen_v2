<?php

namespace Tests\Unit\Imports;

use App\Imports\StudentRowsImport;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

// Task 38: reader maps columns A-E by position, ignores the title/header rows, skips fully-blank
// rows, and tags each kept row with its spreadsheet row number (data starts at row 3).
class StudentRowsImportTest extends TestCase
{
    public function test_it_reads_positionally_from_row_three(): void
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->setCellValue('A1', 'Qyzen Student Upload Template'); // title banner
        $sheet->fromArray(['user_id', 'given_name', 'surname', 'email', 'role_names'], null, 'A2');
        $sheet->fromArray(['2026-12345', 'Ana', 'Cruz', 'ana@example.com', 'student'], null, 'A3');
        // Row 4 intentionally blank → skipped.
        $sheet->fromArray(['', 'Ben', 'Reyes', 'ben@example.com', 'student|educator'], null, 'A5');

        $tmp = tempnam(sys_get_temp_dir(), 'up').'.xlsx';
        (new Xlsx($book))->save($tmp);

        $import = new StudentRowsImport;
        Excel::import($import, $tmp);
        @unlink($tmp);

        $this->assertCount(2, $import->rows);

        $this->assertSame([
            'user_id' => '2026-12345', 'given_name' => 'Ana', 'surname' => 'Cruz',
            'email' => 'ana@example.com', 'role_names' => 'student', '_row' => 3,
        ], $import->rows[0]);

        // Blank row 4 skipped; second data row keeps its real spreadsheet number (5).
        $this->assertSame('', $import->rows[1]['user_id']);
        $this->assertSame('student|educator', $import->rows[1]['role_names']);
        $this->assertSame(5, $import->rows[1]['_row']);
    }
}
