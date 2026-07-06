<?php

namespace App\Http\Requests;

use App\Models\Permission;
use Illuminate\Foundation\Http\FormRequest;

// F6: bulk-create permissions (repeater). permission_string = "resource:action" is
// server-computed (read-only in UI); uniqueness checked on the computed value.
class StorePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*.resource' => ['required', 'string', 'max:255', 'regex:/^[a-z_]+$/'],
            'permissions.*.action' => ['required', 'string', 'max:255', 'regex:/^[a-z_]+$/'],
            'permissions.*.name' => ['nullable', 'string', 'max:255'],
            'permissions.*.module' => ['nullable', 'string', 'max:255'],
            'permissions.*.description' => ['nullable', 'string', 'max:1000'],
            'permissions.*.is_active' => ['required', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $seen = [];
            foreach ((array) $this->input('permissions', []) as $i => $p) {
                $string = ($p['resource'] ?? '').':'.($p['action'] ?? '');
                if (isset($seen[$string])) {
                    $v->errors()->add("permissions.{$i}.action", "Duplicate permission '{$string}' in this batch.");
                }
                $seen[$string] = true;
                if (Permission::where('permission_string', $string)->exists()) {
                    $v->errors()->add("permissions.{$i}.action", "Permission '{$string}' already exists.");
                }
            }
        });
    }
}
