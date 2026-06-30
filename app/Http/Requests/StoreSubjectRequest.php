<?php

namespace App\Http\Requests;

use App\Models\Subject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

// G3: create subject — one row per selected section (Cartesian). Case-insensitive code+name
// uniqueness per section, scoped to this educator.
class StoreSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject_code' => ['required', 'string', 'max:255'],
            'subject_name' => ['required', 'string', 'max:255'],
            'section_ids'  => ['required', 'array', 'min:1'],
            'section_ids.*' => [Rule::exists('tbl_sections', 'id')->where('educator_id', Auth::id())],
            'is_active'    => ['required', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if ($v->errors()->isNotEmpty()) {
                return;
            }
            foreach ($this->input('section_ids', []) as $sectionId) {
                $exists = Subject::where('educator_id', Auth::id())
                    ->where('sections_id', $sectionId)
                    ->whereRaw('LOWER(subject_code) = ?', [strtolower($this->input('subject_code'))])
                    ->whereRaw('LOWER(subject_name) = ?', [strtolower($this->input('subject_name'))])
                    ->when($this->ignoreRowIds(), fn ($q) => $q->whereNotIn('id', $this->ignoreRowIds()))
                    ->exists();
                if ($exists) {
                    $v->errors()->add('subject_code', "This code+name already exists in one of the selected sections.");

                    return;
                }
            }
        });
    }

    /** @return array<int> row ids to ignore (update replaces a group). */
    protected function ignoreRowIds(): array
    {
        return [];
    }
}
