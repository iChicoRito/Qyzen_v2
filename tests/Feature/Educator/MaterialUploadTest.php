<?php

namespace Tests\Feature\Educator;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\LearningMaterial;
use App\Models\Role;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// Task 29: multi-subject upload shares one storage object; orphan cleanup only removes it
// once no row references it anymore; unsupported extensions are rejected.
class MaterialUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $edu;

    private AcademicTerm $term;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'educator', 'student'] as $name) {
            Role::create(['name' => $name, 'description' => $name, 'is_active' => true]);
        }

        $this->edu = User::factory()->create(['user_type' => 'educator', 'email_verified_at' => now()]);
        $this->edu->roles()->attach(Role::where('name', 'educator')->value('id'));

        $year = AcademicYear::create(['year' => '2025 - 2026']);
        $this->term = AcademicTerm::create(['term_name' => 'Prelim', 'semester' => '1st Semester', 'academic_year_id' => $year->id]);

        Storage::fake('local');
    }

    public function test_uploading_to_two_subjects_stores_the_file_once_and_creates_two_rows(): void
    {
        [$subjectA, $subjectB] = [$this->subject(), $this->subject()];

        $this->actingAs($this->edu)->post(route('educator.materials.store'), [
            'subject_ids' => [$subjectA->id, $subjectB->id],
            'files' => [UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf')],
        ])->assertRedirect(route('educator.materials.index'));

        $rows = LearningMaterial::where('educator_id', $this->edu->id)->get();
        $this->assertCount(2, $rows);
        $this->assertSame($rows[0]->storage_path, $rows[1]->storage_path);
        Storage::disk('local')->assertExists($rows[0]->storage_path);
    }

    public function test_deleting_one_of_two_referencing_rows_keeps_the_file_until_the_last_is_gone(): void
    {
        [$subjectA, $subjectB] = [$this->subject(), $this->subject()];

        $this->actingAs($this->edu)->post(route('educator.materials.store'), [
            'subject_ids' => [$subjectA->id, $subjectB->id],
            'files' => [UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf')],
        ]);
        [$first, $second] = LearningMaterial::where('educator_id', $this->edu->id)->get();
        $path = $first->storage_path;

        $this->actingAs($this->edu)->delete(route('educator.materials.destroy', $first));
        Storage::disk('local')->assertExists($path);

        $this->actingAs($this->edu)->delete(route('educator.materials.destroy', $second));
        Storage::disk('local')->assertMissing($path);
    }

    public function test_unsupported_extension_is_rejected(): void
    {
        $subject = $this->subject();

        $this->actingAs($this->edu)->post(route('educator.materials.store'), [
            'subject_ids' => [$subject->id],
            'files' => [UploadedFile::fake()->create('virus.exe', 100)],
        ])->assertSessionHasErrors('files.0');

        $this->assertSame(0, LearningMaterial::count());
    }

    private function subject(): Subject
    {
        $section = Section::create([
            'educator_id' => $this->edu->id, 'academic_term_id' => $this->term->id,
            'section_name' => 'Section'.uniqid(), 'is_active' => true,
        ]);

        return Subject::create([
            'educator_id' => $this->edu->id, 'sections_id' => $section->id,
            'subject_code' => 'CS'.rand(100, 999), 'subject_name' => 'Info Management', 'is_active' => true,
        ]);
    }
}
