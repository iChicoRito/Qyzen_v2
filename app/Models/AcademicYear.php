<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    protected $table = 'tbl_academic_year';

    public $timestamps = false;

    protected $fillable = ['year', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function terms(): HasMany
    {
        return $this->hasMany(AcademicTerm::class, 'academic_year_id');
    }
}
