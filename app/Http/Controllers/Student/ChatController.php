<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Enrolled;
use App\Models\GroupChat;
use App\Models\GroupChatMessage;
use App\Models\GroupChatRead;
use App\Support\TableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

// H10: student chats (request/response). View/send/mark-read only — students CANNOT create
// (no store/destroy routes). Access is gated by GroupChatPolicy (enrollment in the chat's subject).
class ChatController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', GroupChat::class);

        // Chats for subjects the student is actively enrolled in.
        $subjectIds = Enrolled::where('student_id', Auth::id())->where('is_active', true)->pluck('subject_id');

        $query = GroupChat::query()
            ->whereIn('tbl_group_chats.subject_id', $subjectIds)
            ->with('subject:id,subject_code,subject_name')
            ->withCount('messages');
        TableQuery::search($query, $request->query('search'), [
            fn ($q, string $term) => $q->orWhereHas('subject', fn ($s) => $s
                ->where('subject_code', 'like', "%{$term}%")
                ->orWhere('subject_name', 'like', "%{$term}%")),
        ]);
        TableQuery::sort($query, $request, [
            'subject' => function (Builder $q, string $direction): void {
                $q->leftJoin('tbl_subjects as sort_subjects', 'sort_subjects.id', '=', 'tbl_group_chats.subject_id')
                    ->select('tbl_group_chats.*')
                    ->orderBy('sort_subjects.subject_code', $direction)
                    ->orderBy('sort_subjects.subject_name', $direction)
                    ->orderBy('tbl_group_chats.id', 'desc');
            },
            'messages' => 'messages_count',
            'id' => 'id',
        ], 'id', 'desc');

        $chats = $query->paginate(TableQuery::perPage($request))->withQueryString();

        return view('student.chats.index', compact('chats'));
    }

    public function show(GroupChat $chat): View
    {
        $this->authorize('view', $chat); // enrollment-gated

        $messages = $chat->messages()->with('sender:id,given_name,surname')->orderBy('id')->get();

        GroupChatRead::updateOrCreate(
            ['group_chat_id' => $chat->id, 'user_id' => Auth::id()],
            ['last_read_at' => now()],
        );

        return view('student.chats.show', compact('chat', 'messages'));
    }

    public function sendMessage(Request $request, GroupChat $chat): RedirectResponse
    {
        $this->authorize('view', $chat);

        $data = $request->validate(['content' => ['required', 'string', 'max:5000']]);

        GroupChatMessage::create([
            'group_chat_id' => $chat->id,
            'sender_user_id' => Auth::id(),
            'content' => $data['content'],
        ]);

        return back()->with('status', 'Message sent.');
    }
}
