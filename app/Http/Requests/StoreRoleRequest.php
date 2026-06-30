<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// F5: create-role. name matches ^[a-z]+(_[a-z]+)*$ (source Zod).
class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255', 'regex:/^[a-z]+(_[a-z]+)*$/', Rule::unique('tbl_roles', 'name')],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active'   => ['required', 'boolean'],
            'is_system'   => ['required', 'boolean'],
            'permission_ids'   => ['array'],
            'permission_ids.*' => [Rule::exists('tbl_permissions', 'id')],
        ];
    }
}
