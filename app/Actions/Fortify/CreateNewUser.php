<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'given_name' => ['required', 'string', 'max:255'],
            'surname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password' => $this->passwordRules(),
        ])->validate();

        // C2: identity binds by lowercased email. user_id is the historical key —
        // generated here for self-registration; admin-created users (Stage F) set it explicitly.
        $user = new User([
            'given_name' => $input['given_name'],
            'surname' => $input['surname'],
        ]);

        $user->forceFill([
            'user_type' => 'student',
            'user_id' => now()->format('Y').'-'.Str::upper(Str::random(8)),
            'email' => Str::lower($input['email']),
            'is_active' => true,
            'password' => Hash::make($input['password']),
        ])->save();

        return $user;
    }
}
