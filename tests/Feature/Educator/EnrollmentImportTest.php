<?php

namespace Tests\Feature\Educator;

use App\Jobs\ProcessEnrollmentImport;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Enrolled;
use App\Models\EnrollmentImport;
use App\Models\Role;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Excel as ExcelType;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

// Task 39/01: educator bulk enrollment upload - positional read, queued timeline status,
// case-insensitive student match, strict active/inactive status, invalid rows are collected
// into a downloadable report instead of halting the rest of the file.
class EnrollmentImportTest extends TestCase
{
    use RefreshDatabase;

    private User $educator;

    private User $student;

    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'educator', 'student'] as $name) {
            Role::create(['name' => $name, 'description' => $name, 'is_active' => true]);
        }

        $this->educator = User::factory()->educator()->create(['email_verified_at' => now()]);
        $this->educator->roles()->attach(Role::where('name', 'educator')->value('id'));

        // Student user_id has a letter so the upper-cased upload proves case-insensitive matching.
        $this->student = User::factory()->create(['user_id' => 'stud-001', 'email_verified_at' => now()]);
        $this->student->roles()->attach(Role::where('name', 'student')->value('id'));

        $year = AcademicYear::create(['year' => '2025 - 2026']);
        $term = AcademicTerm::create(['term_name' => 'Prelim', 'semester' => '1st Semester', 'academic_year_id' => $year->id]);
        $section = Section::create(['educator_id' => $this->educator->id, 'academic_term_id' => $term->id, 'section_name' => 'Section A', 'is_active' => true]);
        $this->subject = Subject::create(['educator_id' => $this->educator->id, 'sections_id' => $section->id, 'subject_code' => 'MATH101', 'subject_name' => 'Math', 'is_active' => true]);
    }

    public function test_upload_queues_enrollment_import_and_stores_upload(): void
    {
        Storage::fake('local');
        Queue::fake();

        $file = UploadedFile::fake()->create('enrollments.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($this->educator)
            ->post(route('educator.enrollment.import'), ['file' => [$file]])
            ->assertRedirect(route('educator.enrollment.index'))
            ->assertSessionHas('status', 'Enrollment import queued.');

        $import = EnrollmentImport::first();
        $this->assertNotNull($import);
        $this->assertSame('queued', $import->status);
        $this->assertSame('enrollments.xlsx', $import->original_filename);
        Storage::disk('local')->assertExists($import->upload_path);
        Queue::assertPushed(ProcessEnrollmentImport::class, fn (ProcessEnrollmentImport $job) => $job->enrollmentImport->is($import));
        $this->assertSame(0, Enrolled::count());
    }

    public function test_upload_rejects_non_xlsx_file(): void
    {
        Storage::fake('local');
        Queue::fake();

        $csv = UploadedFile::fake()->create('enrollments.csv', 10, 'text/csv');

        $this->actingAs($this->educator)
            ->post(route('educator.enrollment.import'), ['file' => [$csv]])
            ->assertSessionHasErrors('file.0');

        $this->assertNull(EnrollmentImport::first());
        Queue::assertNothingPushed();
    }

    public function test_timeline_endpoint_returns_own_imports_with_active_flag(): void
    {
        EnrollmentImport::create([
            'initiated_by_user_id' => $this->educator->id,
            'original_filename' => 'enrollments.xlsx',
            'upload_path' => 'imports/uploads/enrollments.xlsx',
            'status' => 'processing',
        ]);

        $other = $this->makeEducator();
        EnrollmentImport::create([
            'initiated_by_user_id' => $other->id,
            'original_filename' => 'other.xlsx',
            'upload_path' => 'imports/uploads/other.xlsx',
            'status' => 'processing',
        ]);

        $this->actingAs($this->educator)
            ->get(route('educator.enrollment.imports.timeline'))
            ->assertOk()
            ->assertSee('enrollments.xlsx')
            ->assertDontSee('other.xlsx')
            ->assertSee('data-active="1"', false);
    }

    public function test_import_detail_modal_is_owner_only(): void
    {
        $import = EnrollmentImport::create([
            'initiated_by_user_id' => $this->educator->id,
            'original_filename' => 'enrollments.xlsx',
            'upload_path' => 'imports/uploads/enrollments.xlsx',
            'status' => 'failed',
            'error_message' => 'Row 3 is invalid.',
        ]);

        $this->actingAs($this->makeEducator())
            ->get(route('educator.enrollment.imports.show', $import))
            ->assertForbidden();

        $this->actingAs($this->educator)
            ->get(route('educator.enrollment.imports.show', ['enrollmentImport' => $import, 'modal' => 1]))
            ->assertOk()
            ->assertSee('enrollments.xlsx')
            ->assertSee('Failed')
            ->assertSee('Row 3 is invalid.');
    }

    public function test_job_creates_enrollment_and_marks_import_completed(): void
    {
        Storage::fake('local');

        $path = 'imports/uploads/enrollments.xlsx';
        Storage::disk('local')->put($path, $this->xlsxRaw([['STUD-001', 'MATH101', 'Section A', 'active']]));

        $import = EnrollmentImport::create([
            'initiated_by_user_id' => $this->educator->id,
            'original_filename' => 'enrollments.xlsx',
            'upload_path' => $path,
            'status' => 'queued',
        ]);

        (new ProcessEnrollmentImport($import))->handle(app(NotificationService::class));

        $import->refresh();
        $this->assertSame('completed', $import->status);
        $this->assertSame(1, $import->created_count);
        Storage::disk('local')->assertMissing($path);
        $this->assertDatabaseHas('tbl_enrolled', [
            'educator_id' => $this->educator->id,
            'student_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'is_active' => true,
        ]);
    }

    public function test_job_collects_invalid_rows_into_a_downloadable_report_instead_of_halting(): void
    {
        Storage::fake('local');

        $path = 'imports/uploads/enrollments.xlsx';
        // Row 3 is invalid (bad status); row 4 is a valid enrollment for the same student — an
        // invalid row must no longer halt the rest of the file (Task 01).
        Storage::disk('local')->put($path, $this->xlsxRaw([
            ['stud-001', 'MATH101', 'Section A', 'nope'],
        ]));

        $import = EnrollmentImport::create([
            'initiated_by_user_id' => $this->educator->id,
            'original_filename' => 'enrollments.xlsx',
            'upload_path' => $path,
            'status' => 'queued',
        ]);

        (new ProcessEnrollmentImport($import))->handle(app(NotificationService::class));

        $import->refresh();
        $this->assertSame('completed', $import->status);
        $this->assertSame(0, $import->created_count);
        $this->assertCount(1, $import->failed_rows);
        $this->assertStringContainsString('Row 3', $import->failed_rows[0]['error']);
        $this->assertNotNull($import->failed_report_path);
        Storage::disk('local')->assertExists($import->failed_report_path);
        Storage::disk('local')->assertMissing($path);
        $this->assertSame(0, Enrolled::count());

        $this->actingAs($this->educator)
            ->get(route('educator.enrollment.import.report', $import))
            ->assertOk();
    }

    public function test_job_imports_valid_rows_and_reports_invalid_ones_in_the_same_file(): void
    {
        Storage::fake('local');

        $path = 'imports/uploads/enrollments.xlsx';
        Storage::disk('local')->put($path, $this->xlsxRaw([
            ['STUD-001', 'MATH101', 'Section A', 'active'],
            ['no-such-student', 'MATH101', 'Section A', 'active'],
        ]));

        $import = EnrollmentImport::create([
            'initiated_by_user_id' => $this->educator->id,
            'original_filename' => 'enrollments.xlsx',
            'upload_path' => $path,
            'status' => 'queued',
        ]);

        (new ProcessEnrollmentImport($import))->handle(app(NotificationService::class));

        $import->refresh();
        $this->assertSame('completed', $import->status);
        $this->assertSame(1, $import->created_count);
        $this->assertCount(1, $import->failed_rows);
        $this->assertNotNull($import->failed_report_path);
        $this->assertSame(1, Enrolled::count());
    }

    private function makeEducator(): User
    {
        $user = User::factory()->educator()->create(['email_verified_at' => now()]);
        $user->roles()->attach(Role::where('name', 'educator')->value('id'));

        return $user;
    }

    /**
     * Build an .xlsx laid out like the template: row 1 title, row 2 header, data from row 3.
     *
     * @param  array<int, array<int, string>>  $dataRows
     */
    private function xlsxRaw(array $dataRows): string
    {
        $rows = array_merge([
            ['Qyzen Enrollment Upload Template', '', '', ''],
            ['student_user_id', 'subject_code', 'section_name', 'status'],
        ], $dataRows);

        $export = new class($rows) implements FromArray
        {
            public function __construct(private array $rows) {}

            public function array(): array
            {
                return $this->rows;
            }
        };

        return Excel::raw($export, ExcelType::XLSX);
    }
}
