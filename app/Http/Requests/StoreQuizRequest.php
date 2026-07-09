<?php

namespace App\Http\Requests;

use App\Models\Assessment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

// Task 51: create a bank question. multiple_choice (choices A-D + single correct key) or
// identification (correct_answer string). Subject must belong to this educator.
class StoreQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject_id' => ['required', Rule::exists('tbl_subjects', 'id')->where('educator_id', Auth::id())],
            // Optional convenience: immediately add this question to one or more assessments'
            // pools, instead of a separate trip to each assessment's Question Pool screen.
            'assessment_ids' => ['nullable', 'array'],
            'assessment_ids.*' => [Rule::exists('tbl_assessments', 'id')->where('educator_id', Auth::id())],
        ] + $this->questionRules();
    }

    // Shared question/answer rules (both create and edit).
    protected function questionRules(): array
    {
        $isMc = $this->input('quiz_type') === 'multiple_choice';

        return [
            'question' => ['required', 'string'],
            'quiz_type' => ['required', Rule::in(['multiple_choice', 'identification'])],
            // MC: radio picks the correct choice key. Identification: repeatable accepted answers.
            'choices' => ['nullable', 'array'],
            'choices.*' => ['nullable', 'string'],
            'correct_answer' => [$isMc ? 'required' : 'nullable', 'string'],
            'answers' => [$isMc ? 'nullable' : 'required', 'array'],
            'answers.*' => ['nullable', 'string'],
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
                if (! array_key_exists((string) $this->input('correct_answer'), (array) $this->input('choices', []))) {
                    $v->errors()->add('correct_answer', 'Select which choice is the correct answer.');
                }
            } else {
                // Identification: at least one non-empty accepted answer.
                $answers = array_filter((array) $this->input('answers', []), fn ($a) => trim((string) $a) !== '');
                if (count($answers) < 1) {
                    $v->errors()->add('answers', 'Enter at least one correct answer.');
                }
            }

        });
    }
}
