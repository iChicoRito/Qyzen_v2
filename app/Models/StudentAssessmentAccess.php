<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAssessmentAccess extends Model
{
    protected $table = 'tbl_student_assessment_access';

    protected $fillable = ['educator_id', 'student_id', 'assessment_id', 'is_active', 'expires_at'];

    // expires_at null = the grant never times out (the pre-Task-24 behavior).
    protected $casts = ['is_active' => 'boolean', 'expires_at' => 'datetime'];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class, 'assessment_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
