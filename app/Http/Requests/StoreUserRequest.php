<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// F2: create-user. Mirrors the source create-user Zod schema.
// Authorization is handled by the controller's policy check (admin route group + UserPolicy).
class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_type' => ['required', Rule::in(['admin', 'student', 'educator'])],
            // student: YYYY-NNNNN, educator/admin: YYYY-NNNN — validated by user_type in withValidator.
            'user_id' => ['required', 'string', 'max:255', Rule::unique('tbl_users', 'user_id')],
            'given_name' => ['required', 'string', 'max:255'],
            'surname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('tbl_users', 'email')],
            'is_active' => ['required', 'boolean'],
            'role_names' => ['required', 'array', 'min:1'],
            'role_names.*' => ['string', Rule::exists('tbl_roles', 'name')],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $type = $this->input('user_type');
            $id = (string) $this->input('user_id');
            // Source format: student YYYY-NNNNN (5 digits), educator/admin YYYY-NNNN (4 digits).
            $pattern = $type === 'student' ? '/^\d{4}-\d{5}$/' : '/^\d{4}-\d{4}$/';
            if ($id !== '' && ! preg_match($pattern, $id)) {
                $v->errors()->add('user_id', "User ID must match the format for a {$type} (YYYY-".($type === 'student' ? 'NNNNN' : 'NNNN').').');
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
