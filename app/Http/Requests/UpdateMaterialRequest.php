<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// G9: edit material metadata — file_name + is_active (soft deactivate leaves storage object).
class UpdateMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file_name' => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
