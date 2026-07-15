<?php

namespace Tests\Feature;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AjaxFormSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $educator;

    private AcademicTerm $term;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'educator', 'student'] as $name) {
            Role::create(['name' => $name, 'description' => $name, 'is_active' => true]);
        }

        $this->admin = $this->userWithRole('admin');
        $this->educator = $this->userWithRole('educator');
        $this->grantEducatorPermission('sections:create');

        $year = AcademicYear::create(['year' => '2025 - 2026']);
        $this->term = AcademicTerm::create([
            'term_name' => 'Prelim',
            'semester' => '1st Semester',
            'academic_year_id' => $year->id,
        ]);
    }

    public function test_ajax_admin_form_redirect_becomes_json(): void
    {
        $this->actingAs($this->admin)
            ->withHeaders($this->ajaxHeaders())
            ->post(route('admin.roles.store'), [
                'name' => 'coordinator',
                'description' => 'Coordinator',
                'is_active' => '1',
                'is_system' => '0',
            ])
            ->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'Role coordinator created.',
                'redirect' => route('admin.roles.index'),
            ]);
    }

    public function test_ajax_educator_form_redirect_becomes_json(): void
    {
        $this->actingAs($this->educator)
            ->withHeaders($this->ajaxHeaders())
            ->post(route('educator.sections.store'), [
                'section_name' => 'BSIT 1A',
                'academic_term_ids' => [$this->term->id],
                'is_active' => '1',
            ])
            ->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'Section created.',
                'redirect' => route('educator.sections.index'),
            ]);
    }

    public function test_ajax_validation_errors_stay_json(): void
    {
        $this->actingAs($this->admin)
            ->withHeaders($this->ajaxHeaders())
            ->post(route('admin.roles.store'), [
                'name' => 'Bad Name',
                'is_active' => '1',
                'is_system' => '0',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Please correct the highlighted fields.')
            ->assertJsonStructure(['errors' => ['name']]);
    }

    public function test_non_ajax_form_still_redirects_with_flash(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.roles.store'), [
                'name' => 'registrar',
                'description' => 'Registrar',
                'is_active' => '1',
                'is_system' => '0',
            ])
            ->assertRedirect(route('admin.roles.index'))
            ->assertSessionHas('status', 'Role registrar created.');
    }

    public function test_app_layout_marks_native_forms_and_loads_global_ajax_submitter(): void
    {
        $html = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('action="'.route('logout').'" data-native-submit', $html);
        $this->assertStringContainsString('X-Requested-With', $html);
        $this->assertStringContainsString("Accept': 'application/json'", $html);
        $this->assertStringContainsString('data-ajax-fragment-swap', $html);
    }

    public function test_global_ajax_submitter_ignores_duplicate_submits_while_a_request_is_in_flight(): void
    {
        $script = file_get_contents(resource_path('views/partials/_form-submit-spinner.blade.php'));

        $this->assertStringContainsString('form.dataset.ajaxSubmitting', $script);
    }

    public function test_confirm_delete_uses_submit_event_for_ajax_interception(): void
    {
        $html = $this->actingAs($this->admin)
            ->get(route('admin.roles.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('requestSubmit', $html);
        $this->assertStringNotContainsString('form.submit(); return;', $html);
    }

    /**
     * @return array<string, string>
     */
    private function ajaxHeaders(): array
    {
        return [
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ];
    }

    private function userWithRole(string $roleName): User
    {
        $user = User::factory()->create(['user_type' => $roleName, 'email_verified_at' => now()]);
        $user->roles()->attach(Role::where('name', $roleName)->value('id'));

        return $user;
    }

    private function grantEducatorPermission(string $permissionString): void
    {
        [$resource, $action] = explode(':', $permissionString);
        $permission = Permission::create([
            'name' => $permissionString,
            'resource' => $resource,
            'action' => $action,
            'permission_string' => $permissionString,
            'description' => $permissionString,
            'module' => $resource,
            'is_active' => true,
        ]);

        Role::where('name', 'educator')->firstOrFail()->permissions()->attach($permission->id);
    }
}
