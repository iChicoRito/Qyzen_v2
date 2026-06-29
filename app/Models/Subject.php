<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    protected $table = 'tbl_subjects';

    protected $fillable = ['educator_id', 'sections_id', 'subject_code', 'subject_name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function educator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'educator_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'sections_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrolled::class, 'subject_id');
    }
}
