<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

// G9: upload materials — files[] × selection (subject+section) owned by this educator.
class StoreMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'files'      => ['required', 'array', 'min:1'],
            'files.*'    => ['file', 'max:20480'], // 20MB/file
            'subject_id' => ['required', Rule::exists('tbl_subjects', 'id')->where('educator_id', Auth::id())],
            'section_id' => ['required', Rule::exists('tbl_sections', 'id')->where('educator_id', Auth::id())],
        ];
    }
}
