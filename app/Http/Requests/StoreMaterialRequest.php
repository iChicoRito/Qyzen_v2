<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

// G9: upload materials — files[] × subject_ids[] (each subject already carries its own
// section) owned by this educator. One row per (file, subject) pair, storage object shared.
class StoreMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['file', 'max:20480', 'mimes:pptx,ppsx,ppt,pdf,docx,doc,rtf'], // 20MB/file
            'subject_ids' => ['required', 'array', 'min:1'],
            'subject_ids.*' => [Rule::exists('tbl_subjects', 'id')->where('educator_id', Auth::id())],
        ];
    }
}
