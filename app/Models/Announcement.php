<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Announcement extends Model
{
    public const PRIVATE_DISK = 'announcement-images';

    protected $table = 'tbl_announcements';

    protected $fillable = [
        'educator_id', 'subject_id', 'is_global', 'title', 'description', 'body', 'images', 'is_active',
    ];

    protected $casts = [
        'is_global' => 'boolean', 'images' => 'array', 'is_active' => 'boolean',
    ];

    public static function readableImageDisk(string $path): ?string
    {
        foreach ([self::PRIVATE_DISK, 'local'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return $disk;
            }
        }

        return null;
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole('educator')) {
            return $query->where('educator_id', $user->id);
        }

        if (! $user->hasRole('student')) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('is_active', true)->where(function (Builder $q) use ($user): void {
            $q->where(function (Builder $global) use ($user): void {
                $global->where('is_global', true)
                    ->whereExists(fn ($enrolled) => $enrolled->selectRaw('1')
                        ->from('tbl_enrolled')
                        ->whereColumn('tbl_enrolled.educator_id', 'tbl_announcements.educator_id')
                        ->where('tbl_enrolled.student_id', $user->id)
                        ->where('tbl_enrolled.is_active', true));
            })->orWhere(function (Builder $subject) use ($user): void {
                $subject->where('is_global', false)
                    ->whereExists(fn ($enrolled) => $enrolled->selectRaw('1')
                        ->from('tbl_enrolled')
                        ->whereColumn('tbl_enrolled.educator_id', 'tbl_announcements.educator_id')
                        ->whereColumn('tbl_enrolled.subject_id', 'tbl_announcements.subject_id')
                        ->where('tbl_enrolled.student_id', $user->id)
                        ->where('tbl_enrolled.is_active', true));
            });
        });
    }

    public function educator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'educator_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}
