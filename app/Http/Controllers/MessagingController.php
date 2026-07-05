<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreConversationMessageRequest;
use App\Http\Requests\UpdateConversationMessageRequest;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\User;
use App\Services\ConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    // GET /messaging/conversations/{conversation} — thread fragment; marks read as a side effect.
    public function show(Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $this->conversations->markRead($conversation, Auth::id());

        $thread = $this->conversations->threadFor($conversation, Auth::user());

        return response()->json([
            'html' => view('layouts.partials._conversation_thread', [
                'conversation' => $conversation,
                'thread' => $thread,
            ])->render(),
        ]);
    }

    // POST /messaging/conversations/{conversation}/read — explicit mark-read (also used by the thread poll).
    public function markRead(Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $this->conversations->markRead($conversation, Auth::id());

        return response()->json(['status' => 'ok']);
    }

    // POST /messaging/conversations/{conversation}/messages — send.
    public function sendMessage(StoreConversationMessageRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('send', $conversation);

        $this->conversations->sendMessage($conversation, Auth::user(), $request->validated('content'));

        $thread = $this->conversations->threadFor($conversation, Auth::user());

        return response()->json([
            'html' => view('layouts.partials._conversation_thread', [
                'conversation' => $conversation,
                'thread' => $thread,
            ])->render(),
        ]);
    }

    // PUT /messaging/messages/{message} — edit own message.
    public function updateMessage(UpdateConversationMessageRequest $request, ConversationMessage $message): JsonResponse
    {
        $this->authorize('update', $message);

        $this->conversations->editMessage($message, $request->validated('content'));

        $thread = $this->conversations->threadFor($message->conversation, Auth::user());

        return response()->json([
            'html' => view('layouts.partials._conversation_thread', [
                'conversation' => $message->conversation,
                'thread' => $thread,
            ])->render(),
        ]);
    }

    // DELETE /messaging/messages/{message} — soft marker delete (content blanked, not row-removed).
    public function destroyMessage(ConversationMessage $message): JsonResponse
    {
        $this->authorize('delete', $message);

        $this->conversations->deleteMessage($message);

        $thread = $this->conversations->threadFor($message->conversation, Auth::user());

        return response()->json([
            'html' => view('layouts.partials._conversation_thread', [
                'conversation' => $message->conversation,
                'thread' => $thread,
            ])->render(),
        ]);
    }
}
