<?php

namespace Tests\Feature\Admin;

use App\Jobs\DispatchStudentImport;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserImport;
use App\Notifications\AccountCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

// Stage F: admin feature tests. Authz gate (non-admin 403) + per-module CRUD behavior.
// Reuses the AuthorizationMatrixTest helper patterns (makeUser, RefreshDatabase).
class AdminFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $educator;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'educator', 'student'] as $name) {
            Role::create(['name' => $name, 'description' => $name, 'is_active' => true]);
        }

        $this->admin = $this->makeUser('admin', 'admin');
        $this->educator = $this->makeUser('educator', 'educator');
    }

    // ---- authz gate ----

    public function test_non_admin_is_forbidden_from_admin_routes(): void
    {
        foreach (['admin.dashboard', 'admin.users.index', 'admin.roles.index', 'admin.permissions.index', 'admin.academic-years.index', 'admin.academic-terms.index'] as $route) {
            $this->actingAs($this->educator)->get(route($route))
                ->assertStatus(302); // RequireRole bounces to own dashboard
        }
    }

    public function test_admin_can_view_dashboard_and_lists(): void
    {
        foreach (['admin.dashboard', 'admin.users.index', 'admin.roles.index', 'admin.permissions.index', 'admin.academic-years.index', 'admin.academic-terms.index'] as $route) {
            $this->actingAs($this->admin)->get(route($route))->assertOk();
        }
    }

    // ---- F2 / F4 users ----

    public function test_admin_creates_user_with_roles_and_sends_account_credentials(): void
    {
        Notification::fake();

        $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'user_type' => 'student', 'user_id' => '2026-12345',
            'given_name' => 'New', 'surname' => 'Student', 'email' => 'new.student@example.com',
            'is_active' => '1', 'role_names' => ['student'],
        ])->assertRedirect(route('admin.users.index'));

        $user = User::where('user_id', '2026-12345')->firstOrFail();
        $this->assertSame('new.student@example.com', $user->email);
        $this->assertTrue($user->hasRole('student'));
        $this->assertNotNull($user->password);
        $this->assertTrue($user->must_change_password);
        Notification::assertSentTo($user, AccountCreatedNotification::class, function (AccountCreatedNotification $notification) use ($user) {
            $this->assertSame('Mr. Mark Adrianne Salunga', $notification->createdBy);
            $this->assertTrue(Hash::check($notification->temporaryPassword, $user->fresh()->password));

            return true;
        });
    }

    public function test_admin_bulk_import_queues_student_import_and_stores_upload(): void
    {
        Storage::fake('local');
        Queue::fake();

        $file = UploadedFile::fake()->create('students.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($this->admin)->post(route('admin.users.import'), [
            'file' => [$file],
        ])->assertRedirect(route('admin.users.index'));

        $import = UserImport::first();
        $this->assertNotNull($import);
        $this->assertSame('queued', $import->status);
        $this->assertSame('students.xlsx', $import->original_filename);
        Storage::disk('local')->assertExists($import->upload_path);
        Queue::assertPushed(DispatchStudentImport::class, fn (DispatchStudentImport $job) => $job->userImport->is($import));
    }

    public function test_admin_bulk_import_rejects_non_xlsx_upload(): void
    {
        Storage::fake('local');
        Queue::fake();

        $csv = UploadedFile::fake()->create('students.csv', 10, 'text/csv');

        $this->actingAs($this->admin)->post(route('admin.users.import'), [
            'file' => [$csv],
        ])->assertSessionHasErrors('file.0');

        $this->assertNull(UserImport::first());
        Queue::assertNothingPushed();
    }

    public function test_import_timeline_endpoint_returns_own_imports_with_active_flag(): void
    {
        UserImport::create([
            'initiated_by_user_id' => $this->admin->id,
            'original_filename' => 'students.xlsx',
            'upload_path' => 'imports/uploads/students.xlsx',
            'status' => 'processing',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.users.imports.timeline'))
            ->assertOk()
            ->assertSee('students.xlsx')
            ->assertSee('data-active="1"', false);
    }

    public function test_clear_history_removes_only_the_owners_import_records_and_files(): void
    {
        Storage::fake('local');

        $own = UserImport::create([
            'initiated_by_user_id' => $this->admin->id,
            'original_filename' => 'mine.xlsx',
            'upload_path' => 'imports/uploads/mine.xlsx',
            'failed_report_path' => 'imports/failed/mine.xlsx',
            'status' => 'failed',
        ]);
        $otherAdmin = $this->makeUser('admin', 'admin');
        $other = UserImport::create([
            'initiated_by_user_id' => $otherAdmin->id,
            'original_filename' => 'other.xlsx',
            'upload_path' => 'imports/uploads/other.xlsx',
            'failed_report_path' => 'imports/failed/other.xlsx',
            'status' => 'failed',
        ]);
        Storage::disk('local')->put($own->upload_path, 'upload');
        Storage::disk('local')->put($own->failed_report_path, 'report');
        Storage::disk('local')->put($other->upload_path, 'upload');
        Storage::disk('local')->put($other->failed_report_path, 'report');

        $this->actingAs($this->admin)
            ->delete(route('admin.users.imports.clear'))
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('tbl_user_imports', ['id' => $own->id]);
        $this->assertDatabaseHas('tbl_user_imports', ['id' => $other->id]);
        Storage::disk('local')->assertMissing($own->upload_path);
        Storage::disk('local')->assertMissing($own->failed_report_path);
        Storage::disk('local')->assertExists($other->upload_path);
        Storage::disk('local')->assertExists($other->failed_report_path);
    }

    public function test_import_detail_modal_shows_failure_reasons_to_owner_only(): void
    {
        $import = UserImport::create([
            'initiated_by_user_id' => $this->admin->id,
            'original_filename' => 'students.xlsx',
            'upload_path' => 'imports/uploads/students.xlsx',
            'status' => 'completed',
            'created_count' => 1,
            'failed_count' => 1,
            'failed_rows' => [['email' => 'bad@example.com', 'user_id' => '', 'error' => 'Row 3: invalid email.']],
        ]);

        $this->actingAs($this->makeUser('admin', 'admin'))
            ->get(route('admin.users.imports.show', $import))
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->get(route('admin.users.imports.show', ['userImport' => $import, 'modal' => 1]))
            ->assertOk()
            ->assertSee('students.xlsx')
            ->assertSee('bad@example.com')
            ->assertSee('Row 3: invalid email.');
    }

    public function test_admin_can_download_only_own_failed_import_report(): void
    {
        Storage::fake('local');

        $otherAdmin = $this->makeUser('admin', 'admin');
        $path = 'imports/reports/failed-student-rows.xlsx';
        Storage::disk('local')->put($path, 'report');

        $import = UserImport::create([
            'initiated_by_user_id' => $this->admin->id,
            'original_filename' => 'students.csv',
            'upload_path' => 'imports/uploads/students.csv',
            'status' => 'completed',
            'total_rows' => 2,
            'total_chunks' => 1,
            'processed_chunks' => 1,
            'created_count' => 1,
            'failed_count' => 1,
            'failed_report_path' => $path,
        ]);

        $this->actingAs($otherAdmin)
            ->get(route('admin.users.import.report', $import))
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->get(route('admin.users.import.report', $import))
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_signed_account_confirm_link_marks_user_verified(): void
    {
        $otherAdmin = $this->makeUser('admin', 'admin');
        $inactiveAdmin = $this->makeUser('admin', 'admin');
        $inactiveAdmin->forceFill(['is_active' => false])->save();
        $user = User::factory()->create([
            'user_type' => 'student',
            'email_verified_at' => null,
        ]);
        $user->roles()->attach(Role::where('name', 'student')->value('id'));

        $url = URL::temporarySignedRoute('account.activate', now()->addHour(), ['user' => $user]);

        $this->get($url)
            ->assertRedirect(route('login'));

        $this->assertNotNull($user->fresh()->email_verified_at);
        foreach ([$this->admin, $otherAdmin] as $admin) {
            $this->assertDatabaseHas('tbl_notifications', [
                'recipient_user_id' => $admin->id,
                'actor_user_id' => $user->id,
                'event_type' => 'student_email_verified',
                'link_path' => route('admin.users.show', $user, false),
            ]);
        }
        $this->assertDatabaseMissing('tbl_notifications', [
            'recipient_user_id' => $inactiveAdmin->id,
            'event_type' => 'student_email_verified',
        ]);

        $this->get($url)->assertRedirect(route('login'));
        $this->assertSame(2, \App\Models\Notification::where('event_type', 'student_email_verified')->count());
    }

    public function test_standard_email_verification_notifies_active_admins(): void
    {
        $student = $this->makeUser('student', 'student');
        $student->forceFill(['email_verified_at' => null])->save();
        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $student->getKey(),
            'hash' => sha1($student->getEmailForVerification()),
        ]);

        $this->actingAs($student)->get($url)->assertRedirect();

        $this->assertDatabaseHas('tbl_notifications', [
            'recipient_user_id' => $this->admin->id,
            'actor_user_id' => $student->id,
            'event_type' => 'student_email_verified',
        ]);
    }

    public function test_admin_resend_sends_new_credentials_with_activation_link(): void
    {
        Notification::fake();

        $student = $this->makeUser('student', 'student');
        $student->forceFill([
            'email_verified_at' => null,
            'password' => 'OldPass1!',
            'must_change_password' => false,
        ])->save();

        $this->actingAs($this->admin)
            ->post(route('admin.users.resend-verification', $student))
            ->assertRedirect()
            ->assertSessionHas('status', 'Account credentials and verification link resent.');

        $student->refresh();
        $this->assertTrue($student->must_change_password);
        $this->assertFalse(Hash::check('OldPass1!', $student->password));

        Notification::assertSentTo($student, AccountCreatedNotification::class, function (AccountCreatedNotification $notification) use ($student) {
            $this->assertTrue(Hash::check($notification->temporaryPassword, $student->fresh()->password));
            $mail = $notification->toMail($student);

            $this->assertSame('Your Qyzen account is ready', $mail->subject);
            $this->assertSame('emails.account-created', $mail->view);
            $this->assertSame($notification->temporaryPassword, $mail->viewData['temporaryPassword']);
            $this->assertStringContainsString('/account/activate/'.$student->id, $mail->viewData['confirmUrl']);

            return true;
        });
    }

    public function test_admin_can_correct_email_then_resend_credentials(): void
    {
        Notification::fake();

        $student = $this->makeUser('student', 'student');
        $student->forceFill(['email_verified_at' => null])->save();

        $this->actingAs($this->admin)->put(route('admin.users.update', $student), [
            'user_type' => 'student',
            'user_id' => $student->user_id,
            'given_name' => $student->given_name,
            'surname' => $student->surname,
            'email' => 'corrected.student@example.com',
            'is_active' => '1',
            'role_names' => ['student'],
        ])->assertRedirect(route('admin.users.index'));

        $student->refresh();
        $this->assertSame('corrected.student@example.com', $student->email);

        $this->actingAs($this->admin)
            ->post(route('admin.users.resend-verification', $student))
            ->assertRedirect()
            ->assertSessionHas('status', 'Account credentials and verification link resent.');

        Notification::assertSentTo($student, AccountCreatedNotification::class, function (AccountCreatedNotification $notification) use ($student) {
            $this->assertTrue(Hash::check($notification->temporaryPassword, $student->fresh()->password));

            return true;
        });
    }

    public function test_account_created_email_template_renders_credentials_and_creator(): void
    {
        $user = User::factory()->create([
            'given_name' => 'New',
            'surname' => 'Student',
            'email' => 'new.student@example.com',
        ]);

        $html = view('emails.account-created', [
            'user' => $user,
            'createdBy' => 'Mr. Mark Adrianne Salunga',
            'temporaryPassword' => 'TempPass123',
            'confirmUrl' => 'https://example.test/account/activate/1',
        ])->render();

        $this->assertStringContainsString('Your account is ready.', $html);
        $this->assertStringContainsString('Mr. Mark Adrianne Salunga', $html);
        $this->assertStringContainsString('new.student@example.com', $html);
        $this->assertStringContainsString('TempPass123', $html);
        $this->assertStringContainsString('Confirm account', $html);
    }

    public function test_user_id_format_is_validated_per_type(): void
    {
        // educator must be YYYY-NNNN (4 digits), not 5
        $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'user_type' => 'educator', 'user_id' => '2026-12345',
            'given_name' => 'A', 'surname' => 'B', 'email' => 'edu@example.com',
            'is_active' => '1', 'role_names' => ['educator'],
        ])->assertSessionHasErrors('user_id');
    }

    public function test_admin_edits_user_including_locked_columns(): void
    {
        $target = $this->makeUser('student', 'student');

        $this->actingAs($this->admin)->put(route('admin.users.update', $target), [
            'user_type' => 'educator', 'user_id' => '2026-9999',
            'given_name' => 'Changed', 'surname' => 'Name', 'email' => 'changed@example.com',
            'is_active' => '0', 'role_names' => ['educator'],
        ])->assertRedirect(route('admin.users.index'));

        $target->refresh();
        $this->assertSame('educator', $target->user_type);
        $this->assertSame('changed@example.com', $target->email);
        $this->assertFalse($target->is_active);
        $this->assertTrue($target->hasRole('educator'));
    }

    public function test_users_index_paginates_and_searches_on_the_server(): void
    {
        foreach (range(1, 12) as $i) {
            $student = User::factory()->create([
                'user_type' => 'student',
                'given_name' => 'Paged',
                'surname' => sprintf('Student%02d', $i),
                'email_verified_at' => now(),
            ]);
            $student->roles()->attach(Role::where('name', 'student')->value('id'));
        }

        $this->actingAs($this->admin)
            ->get(route('admin.users.index', ['per_page' => 10, 'sort' => 'member', 'direction' => 'asc']))
            ->assertOk()
            ->assertSee('data-sort="member"', false)
            ->assertSee('Student01')
            ->assertDontSee('Student11')
            ->assertSee('per_page=10', false);

        $this->actingAs($this->admin)
            ->get(route('admin.users.index', ['search' => 'Student12', 'per_page' => 10]))
            ->assertOk()
            ->assertSee('Student12')
            ->assertDontSee('Student01');
    }

    public function test_admin_deletes_user(): void
    {
        $target = $this->makeUser('student', 'student');

        $this->actingAs($this->admin)->delete(route('admin.users.destroy', $target))
            ->assertRedirect(route('admin.users.index'));

        $this->assertNull(User::find($target->id)); // hard delete (forceDelete)
    }

    public function test_roles_index_keeps_modal_outside_ancestor_forms(): void
    {
        $role = Role::create([
            'name' => 'coordinator',
            'description' => 'Coordinator',
            'is_active' => true,
            'is_system' => false,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.roles.index'))
            ->assertOk();

        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);

        $tableRoot = $xpath->query('//*[@id="roles_table_form"]')->item(0);
        $this->assertNotNull($tableRoot);
        $this->assertSame('div', strtolower($tableRoot->nodeName));
        $this->assertSame(0, $xpath->query('//*[@id="form_modal"]/ancestor::form')->length);
        $this->assertSame(0, $xpath->query('//form[@action="'.route('admin.roles.destroy', $role).'"]//*[@id="form_modal"]')->length);
    }

    public function test_users_index_keeps_modal_outside_ancestor_forms(): void
    {
        $this->assertIndexModalIsolated('admin.users.index', 'users_table');
    }

    public function test_permissions_index_keeps_modal_outside_ancestor_forms(): void
    {
        $this->assertIndexModalIsolated('admin.permissions.index', 'permissions_table');
    }

    public function test_academic_years_index_keeps_modal_outside_ancestor_forms(): void
    {
        $this->assertIndexModalIsolated('admin.academic-years.index', 'years_table');
    }

    public function test_academic_terms_index_keeps_modal_outside_ancestor_forms(): void
    {
        $this->assertIndexModalIsolated('admin.academic-terms.index', 'terms_table');
    }

    // ---- F5 roles ----

    public function test_admin_creates_role_and_syncs_permissions(): void
    {
        $p1 = Permission::create(['name' => 'sections:view', 'resource' => 'sections', 'action' => 'view', 'permission_string' => 'sections:view', 'description' => 'x', 'module' => 'sections', 'is_active' => true]);
        $p2 = Permission::create(['name' => 'sections:create', 'resource' => 'sections', 'action' => 'create', 'permission_string' => 'sections:create', 'description' => 'x', 'module' => 'sections', 'is_active' => true]);

        $this->actingAs($this->admin)->post(route('admin.roles.store'), [
            'name' => 'coordinator', 'description' => 'x', 'is_active' => '1', 'is_system' => '0',
            'permission_ids' => [$p1->id, $p2->id],
        ])->assertRedirect(route('admin.roles.index'));

        $role = Role::where('name', 'coordinator')->firstOrFail();
        $this->assertEqualsCanonicalizing([$p1->id, $p2->id], $role->permissions->pluck('id')->all());

        // all-or-nothing replace on update
        $this->actingAs($this->admin)->put(route('admin.roles.update', $role), [
            'name' => 'coordinator', 'description' => 'x', 'is_active' => '1', 'is_system' => '0',
            'permission_ids' => [$p1->id],
        ])->assertRedirect();
        $this->assertSame([$p1->id], $role->fresh()->permissions->pluck('id')->all());
    }

    public function test_role_name_pattern_is_enforced(): void
    {
        $this->actingAs($this->admin)->post(route('admin.roles.store'), [
            'name' => 'Bad Name', 'is_active' => '1', 'is_system' => '0',
        ])->assertSessionHasErrors('name');
    }

    // ---- F6 permissions ----

    public function test_admin_bulk_creates_permissions_with_computed_string(): void
    {
        $this->actingAs($this->admin)->post(route('admin.permissions.store'), [
            'permissions' => [
                ['resource' => 'quizzes', 'action' => 'view', 'is_active' => '1'],
                ['resource' => 'quizzes', 'action' => 'create', 'is_active' => '1'],
            ],
        ])->assertRedirect(route('admin.permissions.index'));

        $this->assertTrue(Permission::where('permission_string', 'quizzes:view')->exists());
        $this->assertTrue(Permission::where('permission_string', 'quizzes:create')->exists());
    }

    public function test_duplicate_permission_in_batch_is_rejected(): void
    {
        $this->actingAs($this->admin)->post(route('admin.permissions.store'), [
            'permissions' => [
                ['resource' => 'quizzes', 'action' => 'view', 'is_active' => '1'],
                ['resource' => 'quizzes', 'action' => 'view', 'is_active' => '1'],
            ],
        ])->assertSessionHasErrors();
        $this->assertSame(0, Permission::count());
    }

    // ---- F7 academic year cascade ----

    public function test_deleting_year_cascades_to_terms(): void
    {
        $year = AcademicYear::create(['year' => '2025 - 2026']);
        AcademicTerm::create(['term_name' => 'Prelim', 'semester' => '1st Semester', 'academic_year_id' => $year->id]);

        $this->actingAs($this->admin)->delete(route('admin.academic-years.destroy', $year))
            ->assertRedirect(route('admin.academic-years.index'));

        $this->assertNull(AcademicYear::find($year->id));
        $this->assertSame(0, AcademicTerm::where('academic_year_id', $year->id)->count());
    }

    // ---- F8 academic term composite uniqueness ----

    public function test_academic_term_composite_uniqueness(): void
    {
        $year = AcademicYear::create(['year' => '2025 - 2026']);
        AcademicTerm::create(['term_name' => 'Prelim', 'semester' => '1st Semester', 'academic_year_id' => $year->id]);

        $this->actingAs($this->admin)->post(route('admin.academic-terms.store'), [
            'term_name' => 'Prelim', 'semester' => '1st Semester', 'academic_year_id' => $year->id, 'is_active' => '1',
        ])->assertSessionHasErrors('term_name');

        // different semester is allowed
        $this->actingAs($this->admin)->post(route('admin.academic-terms.store'), [
            'term_name' => 'Prelim', 'semester' => '2nd Semester', 'academic_year_id' => $year->id, 'is_active' => '1',
        ])->assertRedirect(route('admin.academic-terms.index'));
        $this->assertSame(2, AcademicTerm::count());
    }

    // ---- helper ----

    private function makeUser(string $type, string $roleName): User
    {
        $user = User::factory()->create(['user_type' => $type, 'email_verified_at' => now()]);
        $user->roles()->attach(Role::where('name', $roleName)->value('id'));

        return $user;
    }

    private function assertIndexModalIsolated(string $routeName, string $tableId): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route($routeName))
            ->assertOk();

        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);

        $tableRoot = $xpath->query(sprintf('//*[@id="%s_form"]', $tableId))->item(0);
        $this->assertNotNull($tableRoot);
        $this->assertSame('div', strtolower($tableRoot->nodeName));
        $this->assertSame(0, $xpath->query('//*[@id="form_modal"]/ancestor::form')->length);
    }
}
