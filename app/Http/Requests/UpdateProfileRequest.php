<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

// H11: self-service profile update. Self-service column lock (was a Postgres trigger):
//  - user_id / user_type / is_active are NEVER editable here (not even in $fillable).
//  - given_name/surname editable by educators/admins; READ-ONLY for students (source rule:
//    "students can update email and media only") — enforced by dropping those keys for students.
//  - email editable by all (uniqueness enforced); picture/cover are media.
class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'email' => ['required', 'email', 'max:255', Rule::unique('tbl_users', 'email')->ignore(Auth::id())],
            'profile_picture' => ['nullable', 'image', 'mimes:png,jpeg,jpg,webp', 'max:2048'],
            'cover_photo' => ['nullable', 'image', 'mimes:png,jpeg,jpg,webp', 'max:2048'],
        ];

        // name editable only for non-students.
        if (! Auth::user()->hasRole('student')) {
            $rules['given_name'] = ['required', 'string', 'max:255'];
            $rules['surname'] = ['required', 'string', 'max:255'];
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => strtolower(trim($this->input('email')))]);
        }
    }
}
