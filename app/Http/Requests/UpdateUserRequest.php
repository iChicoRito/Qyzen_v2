<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// F4: edit-user (was a 🚧 stub in the source). Admin editing OTHER users — the self-service
// column lock only blocks self-edits, so admin may change email/user_type/is_active/roles.
class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            'user_type'  => ['required', Rule::in(['admin', 'student', 'educator'])],
            'user_id'    => ['required', 'string', 'max:255', Rule::unique('tbl_users', 'user_id')->ignore($userId)],
            'given_name' => ['required', 'string', 'max:255'],
            'surname'    => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255', Rule::unique('tbl_users', 'email')->ignore($userId)],
            'is_active'  => ['required', 'boolean'],
            'role_names' => ['required', 'array', 'min:1'],
            'role_names.*' => ['string', Rule::exists('tbl_roles', 'name')],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $type = $this->input('user_type');
            $id = (string) $this->input('user_id');
            $pattern = $type === 'student' ? '/^\d{4}-\d{5}$/' : '/^\d{4}-\d{4}$/';
            if ($id !== '' && ! preg_match($pattern, $id)) {
                $v->errors()->add('user_id', "User ID must match the format for a {$type}.");
            }
        });
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => strtolower(trim($this->input('email')))]);
        }
    }
}
