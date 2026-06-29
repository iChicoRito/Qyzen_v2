<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicTerm extends Model
{
    protected $table = 'tbl_academic_term';

    public $timestamps = false;

    protected $fillable = ['term_name', 'semester', 'academic_year_id', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function year(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }
}
