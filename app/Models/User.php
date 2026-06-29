<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

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
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'tbl_user_roles', 'user_id', 'role_id')->withTimestamps();
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

