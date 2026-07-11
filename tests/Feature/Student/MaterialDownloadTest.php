<?php

namespace Tests\Feature\Student;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Enrolled;
use App\Models\LearningMaterial;
use App\Models\Role;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MaterialDownloadTest extends TestCase
{
    use RefreshDatabase;

    private User $educator;

    private User $student;

    private User $otherStudent;

    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['educator', 'student'] as $role) {
            Role::create(['name' => $role, 'description' => $role, 'is_active' => true]);
        }

        $this->educator = $this->userWithRole('educator');
        $this->student = $this->userWithRole('student');
        $this->otherStudent = $this->userWithRole('student');

        $year = AcademicYear::create(['year' => '2025 - 2026']);
        $term = AcademicTerm::create([
            'term_name' => 'Prelim',
            'semester' => '1st Semester',
            'academic_year_id' => $year->id,
        ]);
        $section = Section::create([
            'educator_id' => $this->educator->id,
            'academic_term_id' => $term->id,
            'section_name' => 'A1',
        ]);
        $this->subject = Subject::create([
            'educator_id' => $this->educator->id,
            'sections_id' => $section->id,
            'subject_code' => 'M1',
            'subject_name' => 'Math',
        ]);

        Enrolled::create([
            'student_id' => $this->student->id,
            'educator_id' => $this->educator->id,
            'subject_id' => $this->subject->id,
            'is_active' => true,
        ]);
    }

    public function test_enrolled_student_downloads_legacy_material_from_private_storage(): void
    {
        Storage::fake('local')->put('legacy/lesson.pdf', 'lesson');
        $material = $this->material('legacy/lesson.pdf');
        $material->update(['storage_bucket' => 'learning-materials']);

        $this->actingAs($this->student)
            ->get(route('student.materials.download', $material))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=lesson.pdf');
    }

    public function test_missing_material_file_returns_not_found(): void
    {
        $material = $this->material('missing/lesson.pdf');

        $this->actingAs($this->student)
            ->get(route('student.materials.download', $material))
            ->assertNotFound();
    }

    public function test_inactive_material_cannot_be_downloaded(): void
    {
        $material = $this->material('lesson.pdf', false);

        $this->actingAs($this->student)
            ->get(route('student.materials.download', $material))
            ->assertForbidden();
    }

    public function test_unauthorized_student_cannot_download_material(): void
    {
        $material = $this->material('lesson.pdf');

        $this->actingAs($this->otherStudent)
            ->get(route('student.materials.download', $material))
            ->assertForbidden();
    }

    private function material(string $path, bool $active = true): LearningMaterial
    {
        return LearningMaterial::create([
            'educator_id' => $this->educator->id,
            'subject_id' => $this->subject->id,
            'section_id' => $this->subject->sections_id,
            'storage_bucket' => 'local',
            'storage_path' => $path,
            'file_name' => 'lesson.pdf',
            'file_extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 6,
            'is_active' => $active,
        ]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['user_type' => $role, 'email_verified_at' => now()]);
        $user->roles()->attach(Role::where('name', $role)->value('id'));

        return $user;
    }
}
