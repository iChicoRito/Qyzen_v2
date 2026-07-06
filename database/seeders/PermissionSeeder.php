<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

// Seeds the full resource × action permission catalog with readable descriptions
// (shown under each permission in the role modal). Idempotent — safe to re-run.
class PermissionSeeder extends Seeder
{
    // resource key => [singular label, module]
    private array $resources = [
        'sections' => ['section', 'Academics'],
        'subjects' => ['subject', 'Academics'],
        'assessments' => ['assessment', 'Assessments'],
        'quizzes' => ['quiz question', 'Assessments'],
        'materials' => ['learning material', 'Content'],
        'enrollment' => ['enrollment', 'Academics'],
        'scores' => ['score', 'Assessments'],
        'chats' => ['group chat', 'Communication'],
        'users' => ['user', 'Administration'],
        'roles' => ['role', 'Administration'],
        'permissions' => ['permission', 'Administration'],
        'academic_years' => ['academic year', 'Administration'],
        'academic_terms' => ['academic term', 'Administration'],
    ];

    // action => sentence template ({label} = plural resource label)
    private array $actions = [
        'view' => 'View and browse {label}.',
        'create' => 'Create new {label}.',
        'update' => 'Edit and update existing {label}.',
        'delete' => 'Remove {label}.',
    ];

    public function run(): void
    {
        foreach ($this->resources as $resource => [$singular, $module]) {
            $plural = Str::plural($singular);
            foreach ($this->actions as $action => $template) {
                $string = "{$resource}:{$action}";
                Permission::updateOrCreate(
                    ['permission_string' => $string],
                    [
                        'name' => $string,
                        'resource' => $resource,
                        'action' => $action,
                        'description' => str_replace('{label}', $plural, $template),
                        'module' => $module,
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}
