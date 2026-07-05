<?php

namespace Tests\Unit\Services;

use App\Models\Role;
use App\Models\User;
use App\Services\StudentImportRowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentImportRowServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_normalizes_student_import_rows(): void
    {
        $service = app(StudentImportRowService::class);

        $row = $service->normalize([
            'user_id' => ' 2026-12345 ',
            'given_name' => ' New ',
            'surname' => ' Student ',
            'email' => ' NEW.STUDENT@Example.com ',
            'role_names' => ' student | educator ',
            '_row' => 3,
        ]);

        $this->assertSame([
            'user_type' => 'student',
            'user_id' => '2026-12345',
            'given_name' => 'New',
            'surname' => 'Student',
            'email' => 'new.student@example.com',
            'role_names' => ['student', 'educator'],
            'is_active' => true,
            '_row' => 3,
        ], $row);
    }

    public function test_it_rejects_duplicate_email_against_tbl_users(): void
    {
        Role::create(['name' => 'student', 'description' => 'student', 'is_active' => true]);
        User::factory()->create(['email' => 'new.student@example.com']);

        $service = app(StudentImportRowService::class);
        $validator = $service->validate([
            'user_id' => '2026-12345',
            'given_name' => 'New',
            'surname' => 'Student',
            'email' => 'new.student@example.com',
            'role_names' => ['student'],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertSame('That email address is already in use. Please choose another.', $validator->errors()->first('email'));
    }

    public function test_blank_user_id_passes_validation_and_gets_pending_placeholder(): void
    {
        Role::create(['name' => 'student', 'description' => 'student', 'is_active' => true]);

        $service = app(StudentImportRowService::class);
        $validator = $service->validate([
            'user_id' => '',
            'given_name' => 'New',
            'surname' => 'Student',
            'email' => 'blank.id@example.com',
            'role_names' => ['student'],
        ]);

        $this->assertFalse($validator->fails());
        $this->assertMatchesRegularExpression('/^PENDING-[A-Z0-9]{6}$/', $service->placeholderUserId());
    }

    public function test_it_rejects_unknown_roles(): void
    {
        $service = app(StudentImportRowService::class);
        $validator = $service->validate([
            'user_id' => '2026-12345',
            'given_name' => 'New',
            'surname' => 'Student',
            'email' => 'unknown.role@example.com',
            'role_names' => ['not-a-role'],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertNotEmpty($validator->errors()->get('role_names.0'));
    }
}
