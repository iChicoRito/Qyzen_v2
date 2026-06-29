<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentPresence extends Model
{
    protected $table = 'tbl_student_presence';

    protected $fillable = ['student_id', 'last_seen_at', 'current_path'];

    protected $casts = ['last_seen_at' => 'datetime'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
