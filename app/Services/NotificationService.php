<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

// Write-path companion to NotificationAuthorizer (D5): authorizes then inserts.
// Emit is best-effort — a denied or failed notification never blocks the feature action
// (source treated notifications as fire-and-forget side effects).
class NotificationService
{
    public function __construct(private NotificationAuthorizer $authorizer) {}

    /**
     * Emit one notification if the actor is allowed to. Returns the row or null.
     *
     * @param  array{title?:string,message?:string,link_path?:string,subject_id?:int|null,assessment_id?:int|null,section_id?:int|null,metadata?:array}  $payload
     */
    public function emit(User $actor, string $eventType, int $recipientId, array $payload = []): ?Notification
    {
        $context = [
            'subject_id' => $payload['subject_id'] ?? null,
            'assessment_id' => $payload['assessment_id'] ?? null,
        ];

        if (! $this->authorizer->canEmit($actor, $eventType, $recipientId, $context)) {
            return null;
        }

        return Notification::create([
            'recipient_user_id' => $recipientId,
            'actor_user_id' => $actor->id,
            'event_type' => $eventType,
            'title' => $payload['title'] ?? $eventType,
            'message' => $payload['message'] ?? '',
            'link_path' => $payload['link_path'] ?? null,
            'assessment_id' => $payload['assessment_id'] ?? null,
            'subject_id' => $payload['subject_id'] ?? null,
            'section_id' => $payload['section_id'] ?? null,
            'metadata' => $payload['metadata'] ?? null,
            'is_read' => false,
        ]);
    }

    /** Emit the same event to many recipients (e.g. all enrolled students). */
    public function emitToMany(User $actor, string $eventType, array $recipientIds, array $payload = []): int
    {
        $sent = 0;
        foreach (array_unique($recipientIds) as $id) {
            if ($this->emit($actor, $eventType, (int) $id, $payload)) {
                $sent++;
            }
        }

        return $sent;
    }
}
