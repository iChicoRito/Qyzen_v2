<?php

namespace App\Services\Export;

use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// G8/Task 27: builds the styled scores workbook directly against PhpSpreadsheet (already a
// transitive dependency of maatwebsite/excel — no need to fight its FromCollection/WithEvents
// interfaces for this custom multi-block layout). Used for both the single-assessment export
// (one sheet) and the bulk zip (one workbook per subject/section/term group, one sheet per
// assessment in that group).
final class WorkbookBuilder
{
    private const HEADERS = [
        'Student Name', 'Student ID', 'Subject', 'Section', 'Assessment Code', 'Academic Term',
        'Highest Score', 'Total Questions', 'Percentage', 'Status', 'Remark', 'Highest Submitted At',
    ];

    private const COLUMN_WIDTHS = [26, 14, 16, 12, 16, 16, 13, 14, 12, 14, 32, 20];

    private const DARK_FILL = '1E293B';

    private const LIGHT_FILL = 'F1F5F9';

    private const BAND_FILL = 'F8FAFC';

    public static function newWorkbook(): Spreadsheet
    {
        $book = new Spreadsheet;
        $book->removeSheetByIndex(0);

        return $book;
    }

    public static function singleSheetWorkbook(array $meta, array $rows): Spreadsheet
    {
        $book = self::newWorkbook();
        self::addSheet($book, $meta, $rows);

        return $book;
    }

    public static function addSheet(Spreadsheet $book, array $meta, array $rows): void
    {
        $usedNames = array_map(fn (Worksheet $s) => $s->getTitle(), $book->getAllSheets());
        $name = self::sanitizeSheetName($meta['assessmentCode'] ?? $meta['title'] ?? 'Sheet', $usedNames);

        $sheet = $book->createSheet();
        $sheet->setTitle($name);

        $lastCol = 'L';
        $summary = $meta['summary'] ?? ['enrolled' => 0, 'withSubmission' => 0, 'withoutSubmission' => 0];

        // Row 1: merged title banner.
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->setCellValue('A1', $meta['title'] ?? '');
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 13],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF'.self::DARK_FILL]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(22);

        // Rows 3-6: two-column key/value summary block.
        $left = [
            ['Subject', $meta['subject'] ?? ''],
            ['Section', $meta['section'] ?? ''],
            ['Assessment Code', $meta['assessmentCode'] ?? ''],
            ['Academic Term', $meta['academicTerm'] ?? ''],
        ];
        $right = [
            ['Total Enrolled', $summary['enrolled']],
            ['With Submission', $summary['withSubmission']],
            ['No Submission', $summary['withoutSubmission']],
        ];
        foreach ($left as $i => [$label, $value]) {
            $row = 3 + $i;
            $sheet->setCellValue("A{$row}", $label.':');
            $sheet->mergeCells("B{$row}:D{$row}");
            $sheet->setCellValue("B{$row}", $value);
        }
        foreach ($right as $i => [$label, $value]) {
            $row = 3 + $i;
            $sheet->setCellValue("F{$row}", $label.':');
            $sheet->mergeCells("G{$row}:{$lastCol}{$row}");
            $sheet->setCellValue("G{$row}", $value);
        }
        $sheet->getStyle('A3:'.$lastCol.'6')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF'.self::LIGHT_FILL]],
        ]);
        $sheet->getStyle('A3:A6')->getFont()->setBold(true);
        $sheet->getStyle('F3:F5')->getFont()->setBold(true);

        // Row 8: column headers, frozen.
        $headerRow = 8;
        foreach (self::HEADERS as $i => $header) {
            $col = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue("{$col}{$headerRow}", $header);
        }
        $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF'.self::DARK_FILL]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->freezePane('A'.($headerRow + 1));

        // Rows 9+: one per student, banded + bordered.
        $dataStart = $headerRow + 1;
        foreach (array_values($rows) as $i => $row) {
            $r = $dataStart + $i;
            $values = [
                $row['Student Name'], $row['Student ID'], $row['Subject'], $row['Section'],
                $row['Assessment Code'], $row['Academic Term'], $row['Highest Score'], $row['Total Questions'],
                self::formatPercentage($row['Percentage']), $row['Status'], $row['Remark'],
                self::formatSubmittedAt($row['Highest Submitted At']),
            ];
            foreach ($values as $c => $value) {
                $col = Coordinate::stringFromColumnIndex($c + 1);
                $sheet->setCellValueExplicit("{$col}{$r}", (string) ($value ?? ''), DataType::TYPE_STRING);
            }
            if ($i % 2 === 1) {
                $sheet->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF'.self::BAND_FILL]],
                ]);
            }
        }
        $lastRow = $dataStart + count($rows) - 1;
        if ($lastRow >= $dataStart) {
            $sheet->getStyle("A{$headerRow}:{$lastCol}{$lastRow}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);
        }

        foreach (self::COLUMN_WIDTHS as $i => $width) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i + 1))->setWidth($width);
        }
    }

    // Serializes to raw xlsx bytes without touching the filesystem — for embedding into a zip.
    public static function toBytes(Spreadsheet $book): string
    {
        $writer = new Xlsx($book);
        $stream = fopen('php://temp', 'r+');
        $writer->save($stream);
        rewind($stream);
        $bytes = stream_get_contents($stream);
        fclose($stream);

        return $bytes;
    }

    public static function sanitizeSheetName(string $raw, array $alreadyUsed): string
    {
        $clean = preg_replace('/[\[\]:\\\\\/\?\*]/', ' ', $raw);
        $clean = trim(preg_replace('/\s+/', ' ', $clean));
        $clean = $clean !== '' ? $clean : 'Sheet';
        $base = mb_substr($clean, 0, 31);

        $name = $base;
        $suffix = 2;
        while (in_array($name, $alreadyUsed, true)) {
            $tag = " ({$suffix})";
            $name = mb_substr($base, 0, 31 - mb_strlen($tag)).$tag;
            $suffix++;
        }

        return $name;
    }

    private static function formatPercentage(?float $value): string
    {
        return $value === null ? 'Not submitted' : "{$value}%";
    }

    private static function formatSubmittedAt($value): string
    {
        if (! $value) {
            return 'Not submitted';
        }

        return $value instanceof Carbon || $value instanceof \DateTimeInterface
            ? $value->format('Y-m-d H:i')
            : (string) $value;
    }
}
