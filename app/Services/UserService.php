<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;

// F2/F3/F4: user create/update with roles. The self-service-locked columns
// (user_id, email, user_type, is_active) are NOT in User::$fillable, so set them
// explicitly here — the lock only protects against self-edits, not admin management.
class UserService
{
    /** @param array{user_type:string,user_id:string,given_name:string,surname:string,email:string,is_active:bool} $data */
    public function create(array $data, array $roleNames): User
    {
        $user = new User;
        $this->applyLockedColumns($user, $data);
        $user->given_name = $data['given_name'];
        $user->surname = $data['surname'];
        $user->save();

        $this->syncRolesByName($user, $roleNames);

        return $user;
    }

    public function update(User $user, array $data, array $roleNames): User
    {
        $this->applyLockedColumns($user, $data);
        $user->given_name = $data['given_name'];
        $user->surname = $data['surname'];
        $user->save();

        $this->syncRolesByName($user, $roleNames);

        return $user;
    }

    private function applyLockedColumns(User $user, array $data): void
    {
        $user->forceFill([
            'user_type' => $data['user_type'],
            'user_id' => $data['user_id'],
            'email' => $data['email'],
            'is_active' => $data['is_active'],
        ]);
    }

    private function syncRolesByName(User $user, array $roleNames): void
    {
        $ids = Role::whereIn('name', $roleNames)->pluck('id');
        $user->roles()->sync($ids);
    }
}
