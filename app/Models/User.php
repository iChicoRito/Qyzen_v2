<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements CanResetPasswordContract, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use CanResetPassword, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'tbl_users';

    // password is set explicitly (registration/reset), never mass-assigned.
    protected $hidden = ['password', 'remember_token'];

    // user_id, email, user_type, is_active are deliberately NOT fillable —
    // self-service column lock (was a Postgres trigger; enforced in Stage B12 / Form Requests).
    protected $fillable = [
        'given_name',
        'surname',
        'profile_picture',
        'cover_photo',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'password' => 'hashed',
            'email_verified_at' => 'datetime',
            'must_change_password' => 'boolean',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'tbl_user_roles', 'user_id', 'role_id')->withTimestamps();
    }

    /** Display name from the two stored name columns. */
    public function getNameAttribute(): string
    {
        return trim("{$this->given_name} {$this->surname}");
    }

    /**
     * D1: mirror of has_role() — does the user hold this active role?
     */
    public function hasRole(string $name): bool
    {
        return $this->roles()->where('is_active', true)->where('name', $name)->exists();
    }

    /**
     * D1: mirror of user_has_permission() — roles -> role_permissions -> permissions,
     * active roles + active permissions only, matched by permission_string.
     */
    public function hasPermission(string $permissionString): bool
    {
        return $this->roles()
            ->where('tbl_roles.is_active', true)
            ->whereHas('permissions', fn ($q) => $q
                ->where('tbl_permissions.is_active', true)
                ->where('permission_string', $permissionString))
            ->exists();
    }

    /**
     * Primary role for routing: admin > educator > student, falling back to user_type.
     * (Source: fetchAuthContext / getPrimaryRole.)
     */
    public function primaryRole(): string
    {
        $active = $this->roles()->where('is_active', true)->pluck('name');

        foreach (['admin', 'educator', 'student'] as $role) {
            if ($active->contains($role)) {
                return $role;
            }
        }

        return $this->user_type;
    }

    public function dashboardPath(): string
    {
        return '/'.$this->primaryRole().'/dashboard';
    }
}
