<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

// G5: create assessment. Unique per (code, subject, section, term). subject+section owned by educator.
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
            'subject_id' => ['required', Rule::exists('tbl_subjects', 'id')->where('educator_id', Auth::id())],
            'section_id' => ['required', Rule::exists('tbl_sections', 'id')->where('educator_id', Auth::id())],
            'term'       => ['required', Rule::exists('tbl_academic_term', 'id')],
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

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $exists = \App\Models\Assessment::where('assessment_code', $this->input('assessment_code'))
                ->where('subject_id', $this->input('subject_id'))
                ->where('section_id', $this->input('section_id'))
                ->where('term', $this->input('term'))
                ->when($this->ignoreId(), fn ($q) => $q->whereKeyNot($this->ignoreId()))
                ->exists();
            if ($exists) {
                $v->errors()->add('assessment_code', 'An assessment with this code already exists for that subject/section/term.');
            }
        });
    }

    protected function ignoreId(): ?int
    {
        return null;
    }
}
