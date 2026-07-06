<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CreatedCredentialsExport implements FromArray, WithHeadings
{
    public function __construct(private array $rows) {}

    public function array(): array
    {
        return array_map(fn ($row) => [
            $row['user_id'] ?? '',
            $row['name'] ?? '',
            $row['email'] ?? '',
            $row['temporary_password'] ?? '',
        ], $this->rows);
    }

    public function headings(): array
    {
        return ['user_id', 'name', 'email', 'temporary_password'];
    }
}
