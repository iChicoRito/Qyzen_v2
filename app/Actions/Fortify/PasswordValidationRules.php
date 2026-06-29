<?php

namespace App\Actions\Fortify;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     *
     * @return array<int, Rule|array<mixed>|string>
     */
    protected function passwordRules(): array
    {
        // Qyzen policy (FEATURE_MATRIX): >=8 chars, upper+lower+number+special.
        return [
            'required', 'string', 'confirmed',
            Password::min(8)->mixedCase()->numbers()->symbols(),
        ];
    }
}
