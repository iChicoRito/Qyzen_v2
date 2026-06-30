<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

// G7: grant a retake to a student for an assessment owned by this educator.
class GrantRetakeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assessment_id' => ['required', Rule::exists('tbl_assessments', 'id')->where('educator_id', Auth::id())],
            'student_id'    => ['required', Rule::exists('tbl_users', 'id')->where('user_type', 'student')],
            'extra_retake_count' => ['required', 'integer', 'min:1', 'max:50'],
        ];
    }
}
