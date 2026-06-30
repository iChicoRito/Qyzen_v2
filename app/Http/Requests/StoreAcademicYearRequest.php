<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// F7: create academic year. Format "YYYY - YYYY".
class StoreAcademicYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'year'      => ['required', 'string', 'regex:/^\d{4} - \d{4}$/', Rule::unique('tbl_academic_year', 'year')],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
