<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAssessmentExemption extends Model
{
    protected $table = 'tbl_student_assessment_exemptions';

    protected $fillable = ['educator_id', 'student_id', 'assessment_id', 'reason', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class, 'assessment_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
