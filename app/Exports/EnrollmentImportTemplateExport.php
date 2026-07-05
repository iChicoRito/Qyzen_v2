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

// Task 39: the styled bulk-enrollment-upload template. Same PhpSpreadsheet idiom as
// StudentImportTemplateExport (task 38): merged banner, header on row 2, 10 pre-bordered
// blank rows, frozen panes. FromArray returns [] so the sheet exists before AfterSheet runs.
class EnrollmentImportTemplateExport implements FromArray, WithEvents, WithTitle
{
    private const HEADERS = ['student_user_id', 'subject_code', 'section_name', 'status'];

    private const WIDTHS = ['A' => 18, 'B' => 18, 'C' => 24, 'D' => 16];

    public function array(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'Enrollment Upload Template';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Row 1: merged title banner.
                $sheet->mergeCells('A1:D1');
                $sheet->setCellValue('A1', 'Qyzen Enrollment Upload Template');
                $sheet->getStyle('A1:D1')->applyFromArray([
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
                $sheet->getStyle('A2:D2')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF0A0A0A']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD4D4D8']]],
                ]);

                // Rows 3-12: 10 pre-formatted blank data rows.
                $sheet->getStyle('A3:D12')->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFFFFF']],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFE4E4E7']]],
                ]);

                foreach (self::WIDTHS as $col => $width) {
                    $sheet->getColumnDimension($col)->setWidth($width);
                }

                // Keep the title + header rows visible while scrolling (ySplit: 2).
                $sheet->freezePane('A3');
            },
        ];
    }
}
