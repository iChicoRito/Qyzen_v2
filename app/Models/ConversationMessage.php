<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class ConversationMessage extends Model
{
    protected $table = 'tbl_conversation_messages';

    protected $fillable = ['conversation_id', 'sender_user_id', 'content', 'edited_at', 'message_deleted_at'];

    protected $casts = [
        'edited_at' => 'datetime',
        'message_deleted_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function isEdited(): bool
    {
        return $this->edited_at !== null;
    }

    public function isDeleted(): bool
    {
        return $this->message_deleted_at !== null;
    }

    /** What renders in the thread — never the stale content once deleted. */
    public function displayContent(): string
    {
        return $this->isDeleted() ? 'This message was deleted' : $this->content;
    }

    /** Read receipt: has the OTHER participant read past this message's timestamp? */
    public function isReadBy(?Carbon $otherLastReadAt): bool
    {
        return $otherLastReadAt !== null && $otherLastReadAt->gte($this->created_at);
    }
}
