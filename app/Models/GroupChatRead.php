<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupChatRead extends Model
{
    protected $table = 'tbl_group_chat_reads';

    protected $fillable = ['group_chat_id', 'user_id', 'last_read_at'];

    protected $casts = ['last_read_at' => 'datetime'];

    public function groupChat(): BelongsTo
    {
        return $this->belongsTo(GroupChat::class, 'group_chat_id');
    }
}
