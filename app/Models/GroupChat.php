<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupChat extends Model
{
    protected $table = 'tbl_group_chats';

    protected $fillable = ['educator_id', 'subject_id', 'section_id'];

    public function educator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'educator_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(GroupChatMessage::class, 'group_chat_id');
    }
}
