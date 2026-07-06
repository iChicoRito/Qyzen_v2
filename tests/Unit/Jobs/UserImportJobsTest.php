<?php

namespace Tests\Unit\Jobs;

use App\Jobs\DispatchStudentImport;
use App\Jobs\ProcessStudentImportChunk;
use App\Models\Role;
use App\Models\User;
use App\Models\UserImport;
use App\Notifications\AccountCreatedNotification;
use App\Services\StudentImportRowService;
use App\Services\UserOnboardingService;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserImportJobsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin', 'description' => 'admin', 'is_active' => true]);
        Role::create(['name' => 'student', 'description' => 'student', 'is_active' => true]);
    }

    public function test_dispatch_job_schedules_delayed_chunks(): void
    {
        $admin = $this->makeUser('admin', 'admin');
        $import = UserImport::create([
            'initiated_by_user_id' => $admin->id,
            'original_filename' => 'students.csv',
            'upload_path' => 'imports/uploads/students.csv',
            'status' => 'queued',
        ]);

        $chunks = new Collection([
            collect([['user_id' => '2026-12345']]),
            collect([['user_id' => '2026-12346']]),
        ]);

        $jobs = (new DispatchStudentImport($import))->buildChunkJobs($import, $chunks);

        $this->assertCount(2, $jobs);
        $this->assertInstanceOf(ProcessStudentImportChunk::class, $jobs[0]);
        $this->assertSame(0, abs((int) $jobs[0]->delay->diffInSeconds(now(), false)));
        $this->assertGreaterThanOrEqual(StudentImportRowService::CHUNK_DELAY_SECONDS - 1, abs((int) $jobs[1]->delay->diffInSeconds(now(), false)));
    }

    public function test_chunk_job_creates_users_sends_onboarding_and_writes_failed_report(): void
    {
        Storage::fake('local');
        Notification::fake();

        $admin = $this->makeUser('admin', 'admin');
        $import = UserImport::create([
            'initiated_by_user_id' => $admin->id,
            'original_filename' => 'students.xlsx',
            'upload_path' => 'imports/uploads/students.xlsx',
            'status' => 'processing',
            'total_rows' => 3,
            'total_chunks' => 1,
            'failed_rows' => [],
        ]);
        Storage::disk('local')->put($import->upload_path, 'placeholder');

        $rows = [
            [
                'user_type' => 'student',
                'user_id' => '2026-12345',
                'given_name' => 'New',
                'surname' => 'Student',
                'email' => 'new.student@example.com',
                'role_names' => ['student'],
                'is_active' => true,
                '_row' => 3,
            ],
            [
                // Blank user_id → gets a PENDING-xxxxxx placeholder.
                'user_type' => 'student',
                'user_id' => '',
                'given_name' => 'Blank',
                'surname' => 'Ident',
                'email' => 'blank.ident@example.com',
                'role_names' => ['student'],
                'is_active' => true,
                '_row' => 4,
            ],
            [
                'user_type' => 'student',
                'user_id' => 'bad-id',
                'given_name' => 'Bad',
                'surname' => 'Row',
                'email' => 'bad-row@example.com',
                'role_names' => ['student'],
                'is_active' => true,
                '_row' => 5,
            ],
        ];

        (new ProcessStudentImportChunk($import, $rows))->handle(
            app(StudentImportRowService::class),
            app(UserService::class),
            app(UserOnboardingService::class),
        );

        $import->refresh();
        $user = User::where('user_id', '2026-12345')->firstOrFail();
        $placeholder = User::where('user_id', 'like', 'PENDING-%')->firstOrFail();

        $this->assertSame('completed', $import->status);
        $this->assertSame(2, $import->created_count);
        $this->assertSame(1, $import->failed_count);
        $this->assertNotNull($import->failed_report_path);
        Storage::disk('local')->assertExists($import->failed_report_path);
        Storage::disk('local')->assertMissing($import->upload_path);
        $this->assertTrue($user->hasRole('student'));
        $this->assertTrue($user->must_change_password);
        $this->assertTrue($placeholder->hasRole('student'));
        $this->assertTrue($placeholder->must_change_password);
        $this->assertStringStartsWith('Row 5:', $import->failed_rows[0]['error']);
        Notification::assertSentTo($user, AccountCreatedNotification::class, function (AccountCreatedNotification $notification) use ($user) {
            $this->assertSame('Mr. Mark Adrianne Salunga', $notification->createdBy);
            $this->assertTrue(Hash::check($notification->temporaryPassword, $user->fresh()->password));

            return true;
        });
    }

    private function makeUser(string $type, string $roleName): User
    {
        $user = User::factory()->create(['user_type' => $type, 'email_verified_at' => now()]);
        $user->roles()->attach(Role::where('name', $roleName)->value('id'));

        return $user;
    }
}
