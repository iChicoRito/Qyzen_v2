<?php

namespace App\Http\Requests;

use App\Support\AnnouncementHtml;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateAnnouncementRequest extends StoreAnnouncementRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['body' => AnnouncementHtml::sanitize($this->input('body'))]);
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'subject_id' => ['nullable', Rule::exists('tbl_subjects', 'id')->where('educator_id', Auth::id())],
        ]);
    }
}
