<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

// G6: create question. multiple_choice (choices A-D + single correct key) or
// identification (correct_answer string). Assessment must belong to this educator.
class StoreQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assessment_id' => ['required', Rule::exists('tbl_assessments', 'id')->where('educator_id', Auth::id())],
            'question'   => ['required', 'string'],
            'quiz_type'  => ['required', Rule::in(['multiple_choice', 'identification'])],
            'choices'    => ['nullable', 'array'],
            'choices.*'  => ['nullable', 'string'],
            'correct_answer' => ['required', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if ($this->input('quiz_type') === 'multiple_choice') {
                $choices = array_filter((array) $this->input('choices', []), fn ($c) => $c !== null && $c !== '');
                if (count($choices) < 2) {
                    $v->errors()->add('choices', 'Multiple-choice needs at least two choices.');
                }
                if (! array_key_exists($this->input('correct_answer'), (array) $this->input('choices', []))) {
                    $v->errors()->add('correct_answer', 'The correct answer must be one of the choice keys (A–D).');
                }
            }
        });
    }
}
