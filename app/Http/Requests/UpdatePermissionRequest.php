<?php

namespace App\Http\Requests;

use App\Models\Permission;
use Illuminate\Foundation\Http\FormRequest;

// F6: edit-permission (was a 🚧 stub in the source). Re-computes permission_string on change.
class UpdatePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'resource'    => ['required', 'string', 'max:255', 'regex:/^[a-z_]+$/'],
            'action'      => ['required', 'string', 'max:255', 'regex:/^[a-z_]+$/'],
            'name'        => ['nullable', 'string', 'max:255'],
            'module'      => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active'   => ['required', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $string = $this->input('resource').':'.$this->input('action');
            $exists = Permission::where('permission_string', $string)
                ->where('id', '!=', $this->route('permission')->id)
                ->exists();
            if ($exists) {
                $v->errors()->add('action', "Permission '{$string}' already exists.");
            }
        });
    }
}
