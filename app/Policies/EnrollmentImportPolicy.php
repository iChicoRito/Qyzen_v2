<?php

namespace App\Policies;

use App\Models\EnrollmentImport;
use App\Models\User;

class EnrollmentImportPolicy
{
    public function view(User $user, EnrollmentImport $enrollmentImport): bool
    {
        return $user->hasRole('educator') && $enrollmentImport->initiated_by_user_id === $user->id;
    }
}
