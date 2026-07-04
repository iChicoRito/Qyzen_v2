<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Task 27: discriminated bulk-export filter — all | one term | one academic-year+semester pair.
class ExportScoresBulkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['all', 'term', 'semester'])],
            'termId' => ['required_if:type,term', Rule::exists('tbl_academic_term', 'id')],
            'academicYear' => ['required_if:type,semester', 'string'],
            'semester' => ['required_if:type,semester', 'string'],
        ];
    }
}
