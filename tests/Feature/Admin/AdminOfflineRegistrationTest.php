<?php

namespace Tests\Feature\Admin;

use App\Jobs\ProcessStudentImportChunk;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\UserImport;
use App\Notifications\AccountCreatedNotification;
use App\Services\StudentImportRowService;
use App\Services\UserOnboardingService;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminOfflineRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'educator', 'student'] as $name) {
            Role::create(['name' => $name, 'description' => $name, 'is_active' => true]);
        }

        $this->admin = $this->makeUser('admin');
    }

    public function test_admin_toggles_offline_registration_setting(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.settings.update'), ['offline_registration_enabled' => '1'])
            ->assertRedirect();

        $this->assertTrue(SystemSetting::offlineRegistrationEnabled());

        $this->actingAs($this->admin)
            ->put(route('admin.settings.update'), [])
            ->assertRedirect();

        $this->assertFalse(SystemSetting::offlineRegistrationEnabled());
    }

    public function test_offline_student_creation_skips_email_and_flashes_password(): void
    {
        Notification::fake();
        SystemSetting::setOfflineRegistrationEnabled(true);

        $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'user_type' => 'student',
            'user_id' => '2026-12345',
            'given_name' => 'New',
            'surname' => 'Student',
            'email' => 'offline.student@example.com',
            'is_active' => '0',
            'role_names' => ['student'],
        ])->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('created_credentials');

        $user = User::where('user_id', '2026-12345')->firstOrFail();
        $password = session('created_credentials')[0]['temporary_password'];

        $this->assertTrue($user->is_active);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue($user->must_change_password);
        $this->assertTrue(Hash::check($password, $user->password));
        Notification::assertNothingSent();
    }

    public function test_offline_mode_does_not_skip_email_for_non_students(): void
    {
        Notification::fake();
        SystemSetting::setOfflineRegistrationEnabled(true);

        $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'user_type' => 'educator',
            'user_id' => '2026-1234',
            'given_name' => 'New',
            'surname' => 'Educator',
            'email' => 'new.educator@example.com',
            'is_active' => '1',
            'role_names' => ['educator'],
        ])->assertRedirect(route('admin.users.index'))
            ->assertSessionMissing('created_credentials');

        Notification::assertSentTo(User::where('user_id', '2026-1234')->firstOrFail(), AccountCreatedNotification::class);
    }

    public function test_admin_can_mark_users_verified_or_unverified(): void
    {
        $student = $this->makeUser('student', ['email_verified_at' => null]);

        $this->actingAs($this->admin)
            ->put(route('admin.users.verification', $student), ['verified' => '1'])
            ->assertRedirect();
        $this->assertNotNull($student->fresh()->email_verified_at);

        $this->actingAs($this->admin)
            ->put(route('admin.users.verification', $student), ['verified' => '0'])
            ->assertRedirect();
        $this->assertNull($student->fresh()->email_verified_at);
    }

    public function test_offline_import_collects_credentials_and_download_is_owner_only(): void
    {
        Notification::fake();
        $import = UserImport::create([
            'initiated_by_user_id' => $this->admin->id,
            'original_filename' => 'students.xlsx',
            'upload_path' => 'imports/uploads/students.xlsx',
            'status' => 'processing',
            'total_rows' => 1,
            'total_chunks' => 1,
            'failed_rows' => [],
        ]);

        (new ProcessStudentImportChunk($import, [[
            'user_type' => 'student',
            'user_id' => '2026-55555',
            'given_name' => 'Import',
            'surname' => 'Student',
            'email' => 'import.student@example.com',
            'role_names' => ['student'],
            'is_active' => true,
            '_row' => 3,
        ]], true))->handle(
            app(StudentImportRowService::class),
            app(UserService::class),
            app(UserOnboardingService::class),
        );

        $import->refresh();
        $user = User::where('user_id', '2026-55555')->firstOrFail();

        $this->assertNotEmpty($import->created_credentials);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check($import->created_credentials[0]['temporary_password'], $user->password));
        Notification::assertNothingSent();

        $otherAdmin = $this->makeUser('admin');
        $this->actingAs($otherAdmin)
            ->get(route('admin.users.import.credentials', $import))
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->get(route('admin.users.import.credentials', $import))
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_guest_cannot_download_database_backup(): void
    {
        $this->get(route('admin.settings.database.download'))->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_download_database_backup(): void
    {
        $student = $this->makeUser('student');

        $this->actingAs($student)
            ->get(route('admin.settings.database.download'))
            ->assertStatus(302); // RequireRole bounces to own dashboard before the policy check
    }

    public function test_admin_can_download_database_backup(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.settings.database.download'))
            ->assertOk()
            ->assertHeader('content-disposition');

        $content = $response->streamedContent();

        $this->assertStringContainsString('CREATE TABLE', $content);
        $this->assertStringContainsString('tbl_users', $content);
        $this->assertStringContainsString($this->admin->email, $content);
    }

    private function makeUser(string $type, array $attrs = []): User
    {
        $user = User::factory()->create(['user_type' => $type, 'email_verified_at' => now(), ...$attrs]);
        $user->roles()->attach(Role::where('name', $type)->value('id'));

        return $user;
    }
}
