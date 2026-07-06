<?php

namespace App\Http\Requests;

// G3: edit a subject group (code+name). Replaces all rows in the group; uniqueness excludes
// the group's own row ids. The group is identified by the hidden row_ids[] field.
class UpdateSubjectRequest extends StoreSubjectRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'row_ids' => ['required', 'array', 'min:1'],
            'row_ids.*' => ['integer'],
        ];
    }

    protected function ignoreRowIds(): array
    {
        return array_map('intval', $this->input('row_ids', []));
    }
}
