<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// F7: edit academic year (was a 🚧 stub in the source).
class UpdateAcademicYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'year'      => ['required', 'string', 'regex:/^\d{4} - \d{4}$/', Rule::unique('tbl_academic_year', 'year')->ignore($this->route('academic_year')->id)],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
