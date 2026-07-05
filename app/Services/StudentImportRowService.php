<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;

class StudentImportRowService
{
    public const CHUNK_SIZE = 100;

    public const CHUNK_DELAY_SECONDS = 10;

    public function normalize(array $row): array
    {
        return [
            'user_type' => 'student',
            'user_id' => trim((string) ($row['user_id'] ?? '')),
            'given_name' => trim((string) ($row['given_name'] ?? '')),
            'surname' => trim((string) ($row['surname'] ?? '')),
            'email' => strtolower(trim((string) ($row['email'] ?? ''))),
            'role_names' => $this->parseRoles($row['role_names'] ?? ''),
            'is_active' => true,
            '_row' => $row['_row'] ?? null,
        ];
    }

    public function validate(array $data): ValidationValidator
    {
        return Validator::make($data, [
            // Optional: blank ids get a PENDING-xxxxxx placeholder at create time.
            'user_id' => ['nullable', 'regex:/^\d{4}-\d{5}$/', 'unique:tbl_users,user_id'],
            'given_name' => ['required', 'string', 'max:255'],
            'surname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:tbl_users,email'],
            'role_names' => ['required', 'array', 'min:1'],
            'role_names.*' => ['string', Rule::exists('tbl_roles', 'name')],
        ]);
    }

    // Placeholder user_id for rows uploaded without one, for later admin correction.
    // ponytail: retry-until-unique; fine at import volumes.
    public function placeholderUserId(): string
    {
        do {
            $id = 'PENDING-'.strtoupper(Str::random(6));
        } while (User::where('user_id', $id)->exists());

        return $id;
    }

    private function parseRoles(mixed $value): array
    {
        return collect(explode('|', (string) $value))
            ->map(fn ($role) => trim($role))
            ->filter()
            ->values()
            ->all();
    }
}
