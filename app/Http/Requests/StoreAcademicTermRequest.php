<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// F8: create academic term. Unique on (term_name, semester, academic_year_id).
// semester enum values come from the migration: '1st Semester' | '2nd Semester'.
class StoreAcademicTermRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'term_name'        => ['required', 'string', 'max:255'],
            'semester'         => ['required', Rule::in(['1st Semester', '2nd Semester'])],
            'academic_year_id' => ['required', Rule::exists('tbl_academic_year', 'id')],
            'is_active'        => ['required', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $exists = \App\Models\AcademicTerm::where('term_name', $this->input('term_name'))
                ->where('semester', $this->input('semester'))
                ->where('academic_year_id', $this->input('academic_year_id'))
                ->exists();
            if ($exists) {
                $v->errors()->add('term_name', 'This term already exists for that semester and year.');
            }
        });
    }
}
