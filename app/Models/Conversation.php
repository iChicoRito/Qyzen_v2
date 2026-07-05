<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    protected $table = 'tbl_conversations';

    protected $fillable = ['student_id', 'educator_id'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function educator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'educator_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class, 'conversation_id');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(ConversationRead::class, 'conversation_id');
    }

    // Newest message only — eager-loaded by the conversation list to avoid an N+1 per row.
    public function latestMessage(): HasOne
    {
        return $this->hasOne(ConversationMessage::class, 'conversation_id')->latestOfMany('created_at');
    }

    // Ownership boundary: every list/show query for a signed-in user goes through this.
    public function scopeForParticipant(Builder $query, int $userId): Builder
    {
        return $query->where(fn ($q) => $q->where('student_id', $userId)->orWhere('educator_id', $userId));
    }

    public function otherParticipant(int $userId): User
    {
        return $userId === $this->student_id ? $this->educator : $this->student;
    }
}
