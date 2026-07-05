<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

// Task 33: single "something changed in this thread" ping for send/edit/delete/read. It carries no
// message payload — the client re-fetches the existing server-rendered fragment (WebSocket is the
// trigger, HTTP fragment is the payload). Broadcast to the OTHER participant's messaging.{id} channel.
// ponytail: broadcast now (no queue coupling); move to ShouldBroadcast + a worker only if send
// latency ever shows up at scale.
class ConversationActivity implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(
        public int $recipientId,
        public int $conversationId,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('messaging.'.$this->recipientId);
    }

    public function broadcastAs(): string
    {
        return 'thread.updated';
    }

    public function broadcastWith(): array
    {
        return ['conversationId' => $this->conversationId];
    }
}
