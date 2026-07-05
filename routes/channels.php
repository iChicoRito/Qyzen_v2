<?php

use Illuminate\Support\Facades\Broadcast;

// Task 33: per-user private channel for live messaging. A user may only listen to their own —
// the server broadcasts ConversationActivity here to the OTHER participant on every write.
Broadcast::channel('messaging.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
