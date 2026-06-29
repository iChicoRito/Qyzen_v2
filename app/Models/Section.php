<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    protected $table = 'tbl_sections';

    protected $fillable = ['educator_id', 'academic_term_id', 'section_name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function educator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'educator_id');
    }

    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class, 'academic_term_id');
    }

    public function terms(): BelongsToMany
    {
        return $this->belongsToMany(AcademicTerm::class, 'tbl_sections_term', 'section_id', 'academic_term_id');
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class, 'sections_id');
    }
}
