<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

// G5: create assessment. Any number of assessments allowed per subject/section/term.
class StoreAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assessment_code' => ['required', 'string', 'max:255'],
            'subject_ids'   => ['required', 'array', 'min:1'],
            'subject_ids.*' => [Rule::exists('tbl_subjects', 'id')->where('educator_id', Auth::id())],
            'term'          => ['required', Rule::exists('tbl_academic_term', 'id')],
            'time_limit' => ['required', 'string', 'max:255'],
            'cheating_attempts' => ['nullable', 'integer', 'min:0'],
            'is_shuffle'   => ['required', 'boolean'],
            'allow_review' => ['required', 'boolean'],
            'allow_retake' => ['required', 'boolean'],
            'retake_count' => ['nullable', 'integer', 'min:0'],
            'allow_hint'   => ['required', 'boolean'],
            'hint_count'   => ['nullable', 'integer', 'min:0'],
            'is_active'    => ['required', 'boolean'],
            'start_date'   => ['required', 'date'],
            'end_date'     => ['required', 'date', 'after_or_equal:start_date'],
            'start_time'   => ['required'],
            'end_time'     => ['required'],
        ];
    }

}
