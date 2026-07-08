<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// G6: the styled bulk-quiz-upload template. Same PhpSpreadsheet idiom as
// EnrollmentImportTemplateExport (task 39) / StudentImportTemplateExport (task 38): merged
// banner, header on row 2, 10 pre-bordered blank rows (with 2 filled as examples), frozen panes.
// FromArray returns [] so the sheet exists before AfterSheet runs.
class QuizUploadTemplateExport implements FromArray, WithEvents, WithTitle
{
    private const HEADERS = ['question', 'quiz_type', 'choice_a', 'choice_b', 'choice_c', 'choice_d', 'correct_answer'];

    private const WIDTHS = ['A' => 40, 'B' => 16, 'C' => 16, 'D' => 16, 'E' => 16, 'F' => 16, 'G' => 18];

    private const EXAMPLES = [
        ['What is 2+2?', 'multiple_choice', '3', '4', '5', '6', 'B'],
        ['Capital of France?', 'identification', '', '', '', '', 'Paris'],
    ];

    public function array(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'Quiz Upload Template';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Row 1: merged title banner.
                $sheet->mergeCells('A1:G1');
                $sheet->setCellValue('A1', 'Qyzen Quiz Upload Template');
                $sheet->getStyle('A1:G1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF171717']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Row 2: column headers.
                foreach (self::HEADERS as $i => $header) {
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 1).'2', $header);
                }
                $sheet->getStyle('A2:G2')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF0A0A0A']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD4D4D8']]],
                ]);

                // Rows 3-12: 10 pre-formatted blank data rows; rows 3-4 pre-filled as examples.
                $sheet->getStyle('A3:G12')->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFFFFF']],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFE4E4E7']]],
                ]);
                foreach (self::EXAMPLES as $r => $row) {
                    foreach ($row as $i => $value) {
                        $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 1).(3 + $r), $value);
                    }
                }

                foreach (self::WIDTHS as $col => $width) {
                    $sheet->getColumnDimension($col)->setWidth($width);
                }

                // Keep the title + header rows visible while scrolling (ySplit: 2).
                $sheet->freezePane('A3');
            },
        ];
    }
}
