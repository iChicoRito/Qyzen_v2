<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// F5: edit-role + assign permissions (all-or-nothing replace). Supports rename.
class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('role')->id;

        return [
            'name'        => ['required', 'string', 'max:255', 'regex:/^[a-z]+(_[a-z]+)*$/', Rule::unique('tbl_roles', 'name')->ignore($id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active'   => ['required', 'boolean'],
            'is_system'   => ['required', 'boolean'],
            'permission_ids'   => ['array'],
            'permission_ids.*' => [Rule::exists('tbl_permissions', 'id')],
        ];
    }
}
