<?php

namespace App\Http\Controllers;

use App\Events\ConversationActivity;
use App\Http\Requests\StoreConversationMessageRequest;
use App\Http\Requests\UpdateConversationMessageRequest;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\User;
use App\Services\ConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

// Task 30: private 1:1 student/educator messaging. Shared (non-role-prefixed) controller,
// same placement as NotificationController — mirrors its JSON-fragment-swap poll pattern.
class MessagingController extends Controller
{
    public function __construct(private ConversationService $conversations) {}

    // GET /messaging/conversations — Inbox tab + chat-drawer list fragment.
    public function conversations(): JsonResponse
    {
        $rows = $this->conversations->conversationListFor(Auth::user());

        return response()->json([
            'unread_count' => $rows->sum('unreadCount'),
            // Cheap change token so the client skips the list re-render when nothing moved.
            'signature' => $rows->map(fn ($row) => ($row->lastMessage->id ?? 0).':'.$row->unreadCount)->implode(','),
            'html' => view('layouts.partials._conversation_list_items', ['rows' => $rows])->render(),
        ]);
    }

    // GET /messaging/contacts — people the caller may start a conversation with (enrollment-based).
    public function contacts(): JsonResponse
    {
        $user = Auth::user();

        return response()->json([
            'html' => view('layouts.partials._conversation_contacts', [
                'contacts' => $this->conversations->contactsFor($user),
                'subjects' => $this->conversations->messagingSubjectsFor($user),
            ])->render(),
        ]);
    }

    // POST /messaging/conversations {other_user_id} — find or start the shared thread.
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'other_user_id' => ['required', Rule::exists('tbl_users', 'id')],
        ]);

        $other = User::findOrFail($data['other_user_id']);

        $this->authorize('create', [Conversation::class, $other]);

        $conversation = $this->conversations->findOrCreateConversation(Auth::user(), $other);

        return response()->json(['conversation_id' => $conversation->id]);
    }

    // GET /messaging/conversations/{conversation} — thread fragment. Marks read as a side effect
    // UNLESS ?peek=1 (the read-only polling path), so frequent polls don't write on every tick.
    public function show(Conversation $conversation, Request $request): JsonResponse
    {
        $this->authorize('view', $conversation);

        if (! $request->boolean('peek')) {
            $this->conversations->markRead($conversation, Auth::id());
        }

        return $this->threadResponse($conversation);
    }

    // POST /messaging/conversations/{conversation}/read — explicit mark-read (also used by the thread poll).
    public function markRead(Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $this->conversations->markRead($conversation, Auth::id());

        // Read receipt changed — let the other party's open thread flip its sent-message ticks live.
        $this->pingOtherParticipant($conversation);

        return response()->json(['status' => 'ok']);
    }

    // POST /messaging/conversations/{conversation}/messages — send.
    public function sendMessage(StoreConversationMessageRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('send', $conversation);

        $this->conversations->sendMessage($conversation, Auth::user(), $request->validated('content'));

        $this->pingOtherParticipant($conversation);

        return $this->threadResponse($conversation);
    }

    // PUT /messaging/messages/{message} — edit own message.
    public function updateMessage(UpdateConversationMessageRequest $request, ConversationMessage $message): JsonResponse
    {
        $this->authorize('update', $message);

        $this->conversations->editMessage($message, $request->validated('content'));

        $this->pingOtherParticipant($message->conversation);

        return $this->threadResponse($message->conversation);
    }

    // DELETE /messaging/messages/{message} — soft marker delete (content blanked, not row-removed).
    public function destroyMessage(ConversationMessage $message): JsonResponse
    {
        $this->authorize('delete', $message);

        $this->conversations->deleteMessage($message);

        $this->pingOtherParticipant($message->conversation);

        return $this->threadResponse($message->conversation);
    }

    // Thread fragment + a cheap change token so a polling client can detect new messages, edits, and
    // deletes, skip the HTML swap when nothing changed, and mark-read only when it actually did.
    private function threadResponse(Conversation $conversation): JsonResponse
    {
        $thread = $this->conversations->threadFor($conversation, Auth::user());
        $contexts = $this->conversations->activeContextsFor($conversation, Auth::user());

        return response()->json([
            'signature' => $this->threadSignature($thread),
            'html' => view('layouts.partials._conversation_thread', [
                'conversation' => $conversation,
                'thread' => $thread,
            ])->render(),
            'context_html' => view('layouts.partials._conversation_context_badges', ['contexts' => $contexts])->render(),
        ]);
    }

    // Newest message id + newest updated_at across the thread. Changes on send/edit/delete; stable
    // otherwise — so the client compares it against the last value to decide whether to re-render.
    private function threadSignature(Collection $thread): string
    {
        $last = $thread->last();
        $maxTs = (int) $thread->max(fn ($row) => optional($row->message->updated_at)->getTimestamp());

        return ($last ? $last->message->id : 0).':'.$maxTs;
    }

    // Task 33: notify the OTHER participant that this thread changed. The acting user already gets
    // fresh HTML from its own response, so only the counterparty needs the push.
    private function pingOtherParticipant(Conversation $conversation): void
    {
        $recipient = $conversation->otherParticipant(Auth::id());

        broadcast(new ConversationActivity($recipient->id, $conversation->id));
    }
}
