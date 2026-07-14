<?php

namespace App\Services;

use App\Models\Enrolled;
use App\Models\User;
use App\Services\Export\WorkbookBuilder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class EnrollmentExportService
{
    public function download(User $educator): StreamedResponse
    {
        $rows = Enrolled::query()
            ->where('educator_id', $educator->id)
            ->with([
                'student:id,given_name,surname,user_id,email',
                'subject:id,subject_code,subject_name,sections_id',
                'subject.section:id,section_name,academic_term_id',
                'subject.section.academicTerm:id,term_name',
            ])
            ->get()
            ->groupBy(fn (Enrolled $row) => implode(':', [
                $row->subject?->section?->academic_term_id ?? 'none',
                $row->subject_id,
                $row->subject?->sections_id ?? 'none',
            ]));

        $tmpDir = storage_path('app/tmp');
        File::ensureDirectoryExists($tmpDir);
        $zipPath = $tmpDir.'/enrollment-export-'.$educator->id.'-'.now()->timestamp.'.zip';

        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $usedPaths = [];
        foreach ($rows as $groupRows) {
            $first = $groupRows->first();
            $book = $this->workbook($groupRows->all());
            $path = $this->sanitizePathSegment($first->subject?->section?->academicTerm?->term_name ?? 'no term')
                .'/'.$this->sanitizePathSegment(($first->subject?->subject_code ?? 'no subject').' '.($first->subject?->subject_name ?? ''))
                .'/'.$this->sanitizePathSegment($first->subject?->section?->section_name ?? 'no section').'.xlsx';

            $unique = $path;
            $suffix = 2;
            while (in_array($unique, $usedPaths, true)) {
                $unique = preg_replace('/\.xlsx$/', "-{$suffix}.xlsx", $path);
                $suffix++;
            }
            $usedPaths[] = $unique;

            $zip->addFromString($unique, WorkbookBuilder::toBytes($book));
        }

        $zip->close();

        $filename = Str::slug($educator->name).'-enrollment-'.now()->format('Y-m-d').'.zip';

        return response()->streamDownload(function () use ($zipPath) {
            readfile($zipPath);
            @unlink($zipPath);
        }, $filename, ['Content-Type' => 'application/zip']);
    }

    /**
     * @param  array<int, Enrolled>  $enrollments
     */
    private function workbook(array $enrollments): Spreadsheet
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Enrollment');

        $headers = ['Student Number', 'Name', 'Email', 'Section', 'Subject Code', 'Subject Name', 'Status'];
        foreach ($headers as $index => $header) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($index + 1).'1', $header);
        }

        foreach (array_values($enrollments) as $index => $enrolled) {
            $row = $index + 2;
            $student = $enrolled->student;
            $subject = $enrolled->subject;
            $values = [
                $student?->user_id,
                $student?->name,
                $student?->email,
                $subject?->section?->section_name,
                $subject?->subject_code,
                $subject?->subject_name,
                $enrolled->is_active ? 'Active' : 'Inactive',
            ];

            foreach ($values as $column => $value) {
                $sheet->setCellValueExplicit(Coordinate::stringFromColumnIndex($column + 1).$row, (string) ($value ?? ''), DataType::TYPE_STRING);
            }
        }

        $lastRow = max(1, count($enrollments) + 1);
        $sheet->getStyle("A1:G{$lastRow}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E293B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        foreach ([16, 26, 28, 18, 16, 28, 12] as $index => $width) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index + 1))->setWidth($width);
        }

        return $book;
    }

    private function sanitizePathSegment(string $name): string
    {
        $upper = strtoupper(trim($name));
        $clean = trim(preg_replace('/[^A-Z0-9]+/', '-', $upper), '-');

        return $clean !== '' ? $clean : 'UNKNOWN';
    }
}
