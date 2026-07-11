<?php

namespace Tests\Feature;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\LearningMaterial;
use App\Models\Role;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LearningMaterialStorageMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_copies_existing_local_materials_to_durable_storage_only_when_readable(): void
    {
        Role::create(['name' => 'educator', 'description' => 'educator', 'is_active' => true]);
        $educator = User::factory()->create(['user_type' => 'educator', 'email_verified_at' => now()]);
        $educator->roles()->attach(Role::where('name', 'educator')->value('id'));

        $year = AcademicYear::create(['year' => '2025 - 2026']);
        $term = AcademicTerm::create(['term_name' => 'Prelim', 'semester' => '1st Semester', 'academic_year_id' => $year->id]);
        $section = Section::create(['educator_id' => $educator->id, 'academic_term_id' => $term->id, 'section_name' => 'A1']);
        $subject = Subject::create(['educator_id' => $educator->id, 'sections_id' => $section->id, 'subject_code' => 'M1', 'subject_name' => 'Math']);

        Storage::fake('local')->put('learning-materials/old.pdf', 'old');
        Storage::fake('learning-materials');

        $copied = $this->material($educator, $subject, 'learning-materials/old.pdf');
        $missing = $this->material($educator, $subject, 'learning-materials/missing.pdf');

        $exitCode = Artisan::call('materials:migrate-storage');

        $this->assertSame(0, $exitCode);
        Storage::disk('learning-materials')->assertExists('learning-materials/old.pdf');
        $this->assertSame('learning-materials', $copied->fresh()->storage_bucket);
        $this->assertSame('local', $missing->fresh()->storage_bucket);
        $this->assertStringContainsString('Copied 1 material file(s); 1 missing.', Artisan::output());
    }

    private function material(User $educator, Subject $subject, string $path): LearningMaterial
    {
        return LearningMaterial::create([
            'educator_id' => $educator->id,
            'subject_id' => $subject->id,
            'section_id' => $subject->sections_id,
            'storage_bucket' => 'local',
            'storage_path' => $path,
            'file_name' => basename($path),
            'file_extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 3,
            'is_active' => true,
        ]);
    }
}
