<?php

namespace Tests\Feature\Educator;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Enrolled;
use App\Models\Role;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;
use ZipArchive;

class EnrollmentExportTest extends TestCase
{
    use RefreshDatabase;

    private User $educator;

    private User $otherEducator;

    private AcademicTerm $term;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'educator', 'student'] as $name) {
            Role::create(['name' => $name, 'description' => $name, 'is_active' => true]);
        }

        $this->educator = $this->makeUser('educator');
        $this->otherEducator = $this->makeUser('educator');

        $year = AcademicYear::create(['year' => '2026 - 2027']);
        $this->term = AcademicTerm::create(['term_name' => 'Prelim', 'semester' => '1st Semester', 'academic_year_id' => $year->id]);
    }

    public function test_download_returns_zip_with_sanitized_grouped_path(): void
    {
        $this->enroll($this->educator, '2026-0001', 'Cruz', 'Ana');

        $response = $this->actingAs($this->educator)
            ->get(route('educator.enrollment.download'))
            ->assertOk();

        $this->assertSame('application/zip', $response->headers->get('Content-Type'));

        $tmp = tempnam(sys_get_temp_dir(), 'enrollment-zip');
        file_put_contents($tmp, $response->streamedContent());

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($tmp) === true);
        $this->assertSame(1, $zip->numFiles);

        $entry = $zip->getNameIndex(0);
        $this->assertStringStartsWith('PRELIM/', $entry);
        $this->assertStringContainsString('/MATH-101-MATH/', $entry);
        $this->assertStringEndsWith('/SECTION-A.xlsx', $entry);

        $zip->close();
        @unlink($tmp);
    }

    public function test_download_contains_only_authenticated_educator_enrollments(): void
    {
        $this->enroll($this->educator, '2026-0001', 'Cruz', 'Ana');
        $this->enroll($this->otherEducator, '2026-9999', 'Hidden', 'Student');

        $response = $this->actingAs($this->educator)
            ->get(route('educator.enrollment.download'))
            ->assertOk();

        $zipPath = tempnam(sys_get_temp_dir(), 'enrollment-zip');
        file_put_contents($zipPath, $response->streamedContent());

        $zip = new ZipArchive;
        $zip->open($zipPath);
        $entry = $zip->getNameIndex(0);
        $xlsxPath = tempnam(sys_get_temp_dir(), 'enrollment-xlsx');
        file_put_contents($xlsxPath, $zip->getFromName($entry));
        $zip->close();

        $sheet = IOFactory::load($xlsxPath)->getActiveSheet();
        $this->assertSame('2026-0001', $sheet->getCell('A2')->getValue());
        $this->assertSame('Ana Cruz', $sheet->getCell('B2')->getValue());
        $this->assertNull($sheet->getCell('A3')->getValue());

        @unlink($xlsxPath);
        @unlink($zipPath);
    }

    private function enroll(User $educator, string $studentNumber, string $surname, string $givenName): void
    {
        $section = Section::create([
            'educator_id' => $educator->id,
            'academic_term_id' => $this->term->id,
            'section_name' => 'Section A',
            'is_active' => true,
        ]);
        $subject = Subject::create([
            'educator_id' => $educator->id,
            'sections_id' => $section->id,
            'subject_code' => 'MATH-101',
            'subject_name' => 'Math',
            'is_active' => true,
        ]);
        $student = $this->makeUser('student', [
            'user_id' => $studentNumber,
            'surname' => $surname,
            'given_name' => $givenName,
        ]);

        Enrolled::create([
            'student_id' => $student->id,
            'educator_id' => $educator->id,
            'subject_id' => $subject->id,
            'is_active' => true,
        ]);
    }

    private function makeUser(string $type, array $attrs = []): User
    {
        $user = User::factory()->create(['user_type' => $type, 'email_verified_at' => now(), ...$attrs]);
        $user->roles()->attach(Role::where('name', $type)->value('id'));

        return $user;
    }
}
