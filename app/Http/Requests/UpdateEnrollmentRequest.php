<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

// G4: edit a single enrollment row — change student/subject/status; uniqueness excludes self.
class UpdateEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', Rule::exists('tbl_users', 'id')->where('user_type', 'student')],
            'subject_id' => ['required', Rule::exists('tbl_subjects', 'id')->where('educator_id', Auth::id())],
            'is_active'  => ['required', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if ($v->errors()->isNotEmpty()) {
                return;
            }
            $dup = \App\Models\Enrolled::where('educator_id', Auth::id())
                ->where('student_id', $this->input('student_id'))
                ->where('subject_id', $this->input('subject_id'))
                ->whereKeyNot($this->route('enrolled')->id)
                ->exists();
            if ($dup) {
                $v->errors()->add('student_id', 'This student is already enrolled in that subject.');
            }
        });
    }
}
