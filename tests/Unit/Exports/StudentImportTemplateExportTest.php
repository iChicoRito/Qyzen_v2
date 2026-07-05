<?php

namespace Tests\Unit\Exports;

use App\Exports\StudentImportTemplateExport;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

// Task 38: the template must match the spec layout exactly (positional reading depends on it).
class StudentImportTemplateExportTest extends TestCase
{
    public function test_template_matches_spec_layout(): void
    {
        $raw = ExcelFacade::raw(new StudentImportTemplateExport, Excel::XLSX);

        $tmp = tempnam(sys_get_temp_dir(), 'tpl').'.xlsx';
        file_put_contents($tmp, $raw);
        $sheet = IOFactory::load($tmp)->getActiveSheet();
        @unlink($tmp);

        $this->assertSame('Student Upload Template', $sheet->getTitle());
        $this->assertSame('Qyzen Student Upload Template', $sheet->getCell('A1')->getValue());
        $this->assertContains('A1:E1', array_keys($sheet->getMergeCells()));

        $this->assertSame(
            ['user_id', 'given_name', 'surname', 'email', 'role_names'],
            [
                $sheet->getCell('A2')->getValue(), $sheet->getCell('B2')->getValue(),
                $sheet->getCell('C2')->getValue(), $sheet->getCell('D2')->getValue(),
                $sheet->getCell('E2')->getValue(),
            ]
        );

        // Frozen below row 2 (ySplit: 2).
        $this->assertSame('A3', $sheet->getFreezePane());

        $this->assertEqualsWithDelta(18, $sheet->getColumnDimension('A')->getWidth(), 0.5);
        $this->assertEqualsWithDelta(36, $sheet->getColumnDimension('D')->getWidth(), 0.5);

        // Row-1 banner fill is the dark spec colour.
        $this->assertSame('FF171717', $sheet->getStyle('A1')->getFill()->getStartColor()->getARGB());
    }
}
