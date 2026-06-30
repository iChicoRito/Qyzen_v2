<?php

namespace App\Imports;

use App\Services\UserService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

// F3: bulk student import. Columns: user_id, given_name, surname, email, status.
// Per-row validated; failures collected (not thrown) so the rest still import,
// matching the source's per-row success/failure report. ponytail: synchronous chunked
// import — move to a queued job only if files grow large enough to time out.
class StudentsImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    private int $created = 0;
    private array $failed = [];

    public function __construct(private UserService $users) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $data = [
                'user_type'  => 'student',
                'user_id'    => trim((string) $row['user_id']),
                'given_name' => trim((string) $row['given_name']),
                'surname'    => trim((string) $row['surname']),
                'email'      => strtolower(trim((string) $row['email'])),
                'is_active'  => filter_var($row['status'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ];

            $validator = Validator::make($data, [
                'user_id'    => ['required', 'regex:/^\d{4}-\d{5}$/', 'unique:tbl_users,user_id'],
                'given_name' => ['required', 'string', 'max:255'],
                'surname'    => ['required', 'string', 'max:255'],
                'email'      => ['required', 'email', 'unique:tbl_users,email'],
            ]);

            if ($validator->fails()) {
                $this->failed[] = $data + ['error' => $validator->errors()->first()];
                continue;
            }

            $this->users->create($data, ['student']);
            $this->created++;
        }
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function createdCount(): int
    {
        return $this->created;
    }

    public function failedRows(): array
    {
        return $this->failed;
    }
}
