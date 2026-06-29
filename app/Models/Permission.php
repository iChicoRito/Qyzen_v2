<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $table = 'tbl_permissions';

    public $timestamps = false;

    protected $fillable = [
        'name', 'resource', 'action', 'description', 'module', 'is_active', 'permission_string',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'tbl_role_permissions', 'permission_id', 'role_id');
    }
}
