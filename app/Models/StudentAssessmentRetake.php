<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAssessmentRetake extends Model
{
    protected $table = 'tbl_student_assessment_retakes';

    protected $fillable = ['educator_id', 'student_id', 'assessment_id', 'extra_retake_count', 'is_active'];

    protected $casts = ['extra_retake_count' => 'integer', 'is_active' => 'boolean'];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class, 'assessment_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
