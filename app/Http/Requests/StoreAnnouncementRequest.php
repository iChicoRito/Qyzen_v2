<?php

namespace App\Http\Requests;

use App\Support\AnnouncementHtml;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['body' => AnnouncementHtml::sanitize($this->input('body'))]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'body' => ['required', 'string', 'max:100000'],
            'subject_id' => ['nullable', Rule::exists('tbl_subjects', 'id')->where('educator_id', Auth::id())],
            'is_global' => ['required', 'boolean'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:10240'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (! $this->boolean('is_global') && ! $this->filled('subject_id')) {
                $validator->errors()->add('subject_id', 'Select a subject or enable the global announcement switch.');
            }
        });
    }
}
