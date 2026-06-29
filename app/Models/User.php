<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $table = 'tbl_users';

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
        ];
    }
}
