<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

// G6: edit one question — a single assessment (can't fan one row into many).
class UpdateQuizRequest extends StoreQuizRequest
{
    public function rules(): array
    {
        return [
            'assessment_id' => ['required', Rule::exists('tbl_assessments', 'id')->where('educator_id', Auth::id())],
        ] + $this->questionRules();
    }
}
