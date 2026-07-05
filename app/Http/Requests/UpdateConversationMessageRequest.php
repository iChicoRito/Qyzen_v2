<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConversationMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // policy authorization happens in the controller against the message
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:5000'],
        ];
    }
}
