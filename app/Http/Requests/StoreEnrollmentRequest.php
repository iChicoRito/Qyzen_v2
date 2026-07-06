<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

// G4: create enrollments — studentIds × subjectIds → one row per pair, unique on
// (student, subject, educator). Subjects must be owned by this educator.
class StoreEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => [Rule::exists('tbl_users', 'id')->where('user_type', 'student')],
            'subject_ids' => ['required', 'array', 'min:1'],
            'subject_ids.*' => [Rule::exists('tbl_subjects', 'id')->where('educator_id', Auth::id())],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
