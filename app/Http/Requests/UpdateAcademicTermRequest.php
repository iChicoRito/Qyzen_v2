<?php

namespace App\Http\Requests;

use App\Models\AcademicTerm;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// F8: edit academic term (was a 🚧 stub in the source).
class UpdateAcademicTermRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'term_name' => ['required', 'string', 'max:255'],
            'semester' => ['required', Rule::in(['1st Semester', '2nd Semester'])],
            'academic_year_id' => ['required', Rule::exists('tbl_academic_year', 'id')],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $exists = AcademicTerm::where('term_name', $this->input('term_name'))
                ->where('semester', $this->input('semester'))
                ->where('academic_year_id', $this->input('academic_year_id'))
                ->where('id', '!=', $this->route('academic_term')->id)
                ->exists();
            if ($exists) {
                $v->errors()->add('term_name', 'This term already exists for that semester and year.');
            }
        });
    }
}
