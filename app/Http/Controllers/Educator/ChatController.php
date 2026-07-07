<?php

namespace App\Http\Controllers\Educator;

use App\Http\Controllers\Controller;
use App\Models\GroupChat;
use App\Models\GroupChatMessage;
use App\Models\GroupChatRead;
use App\Models\Subject;
use App\Support\TableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// G10: group chats, request/response (live delivery deferred to Stage I). Educator owns chats
// (create/delete); send + mark-read work on page load. ponytail: no websockets — plain reloads.
class ChatController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', GroupChat::class);

        $query = GroupChat::query()
            ->where('tbl_group_chats.educator_id', Auth::id())
            ->with(['subject:id,subject_code,subject_name'])
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

        $subjects = Subject::visibleTo(Auth::user())->orderBy('subject_code')->get();

        return view('educator.chats.index', compact('chats', 'subjects'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', GroupChat::class);

        $data = $request->validate([
            'subject_id' => ['required', Rule::exists('tbl_subjects', 'id')->where('educator_id', Auth::id())],
            'section_id' => ['required', Rule::exists('tbl_sections', 'id')->where('educator_id', Auth::id())],
        ]);

        GroupChat::firstOrCreate([
            'educator_id' => Auth::id(),
            'subject_id' => $data['subject_id'],
            'section_id' => $data['section_id'],
        ]);

        return redirect()->route('educator.chats.index')->with('status', 'Chat created.');
    }

    public function show(GroupChat $chat): View
    {
        $this->authorize('view', $chat);

        $messages = $chat->messages()->with('sender:id,given_name,surname')->orderBy('id')->get();

        // Mark read on open (request/response equivalent of the live mark-read upsert).
        GroupChatRead::updateOrCreate(
            ['group_chat_id' => $chat->id, 'user_id' => Auth::id()],
            ['last_read_at' => now()],
        );

        return view('educator.chats.show', compact('chat', 'messages'));
    }

    public function sendMessage(Request $request, GroupChat $chat): RedirectResponse
    {
        $this->authorize('view', $chat); // participant (owner) may post

        $data = $request->validate(['content' => ['required', 'string', 'max:5000']]);

        GroupChatMessage::create([
            'group_chat_id' => $chat->id,
            'sender_user_id' => Auth::id(),
            'content' => $data['content'],
        ]);

        return back()->with('status', 'Message sent.');
    }

    public function destroy(GroupChat $chat): RedirectResponse
    {
        $this->authorize('delete', $chat);

        $chat->messages()->delete();
        $chat->delete();

        return redirect()->route('educator.chats.index')->with('status', 'Chat deleted.');
    }
}
