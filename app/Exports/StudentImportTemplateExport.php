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

// Task 38: the styled bulk-student-upload template. Built directly against PhpSpreadsheet in
// an AfterSheet event (same styling idioms as App\Services\Export\WorkbookBuilder) so the exact
// spec layout — merged banner, header on row 2, 10 pre-bordered blank rows, frozen panes — is
// reproducible. FromArray returns [] purely to guarantee the sheet exists before AfterSheet runs.
class StudentImportTemplateExport implements FromArray, WithEvents, WithTitle
{
    private const HEADERS = ['user_id', 'given_name', 'surname', 'email', 'role_names'];

    private const WIDTHS = ['A' => 18, 'B' => 22, 'C' => 22, 'D' => 36, 'E' => 28];

    public function array(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'Student Upload Template';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Row 1: merged title banner.
                $sheet->mergeCells('A1:E1');
                $sheet->setCellValue('A1', 'Qyzen Student Upload Template');
                $sheet->getStyle('A1:E1')->applyFromArray([
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
                $sheet->getStyle('A2:E2')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF0A0A0A']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD4D4D8']]],
                ]);

                // Rows 3-12: 10 pre-formatted blank data rows.
                $sheet->getStyle('A3:E12')->applyFromArray([
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
