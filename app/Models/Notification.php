<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $table = 'tbl_notifications';

    protected $fillable = [
        'recipient_user_id', 'actor_user_id', 'event_type', 'title', 'message',
        'link_path', 'assessment_id', 'subject_id', 'section_id', 'metadata',
        'is_read', 'read_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    /** Relations the bell renders (actor name/avatar + context for badges / file card). */
    public const BELL_WITH = [
        'actor:id,given_name,surname,profile_picture',
        'subject:id,subject_code,subject_name',
        'section:id,section_name',
        'assessment:id,assessment_code',
    ];

    // link_path was stored via route() which bakes in the scheme+host at creation time, so a
    // notification made under http://127.0.0.1:8000 breaks once the app is served at http://localhost.
    // Render only the path portion so the link always stays on whatever host the user is on. Fixes
    // both existing rows and any future absolute values.
    // ponytail: normalize on read (one place) instead of churning the ~9 route() emit sites; those
    //   can stay absolute — switch them to route(..., [], false) only if link_path is ever rendered elsewhere.
    protected function linkHref(): Attribute
    {
        return Attribute::get(function (): string {
            if (! $this->link_path) {
                return '#';
            }

            $path = parse_url($this->link_path, PHP_URL_PATH);

            return $path ?: '#';
        });
    }

    /** Owner-scoping boundary: every read/count/mark query filters to the signed-in user. */
    public function scopeForRecipient(Builder $query, int $userId): Builder
    {
        return $query->where('recipient_user_id', $userId);
    }

    /** The recent set shown in the bell — used by the view composer and the poll endpoint. */
    public static function recentForBell(int $userId, int $limit = 10)
    {
        return static::forRecipient($userId)->with(self::BELL_WITH)->latest()->limit($limit)->get();
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    // Context for the bell's detail badges / file card (may be null — e.g. deleted assessment).
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class, 'assessment_id');
    }
}
