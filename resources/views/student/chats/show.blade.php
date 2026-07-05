{{-- H10: student chat thread — view + send (cannot create/delete). --}}
@extends('student.layout')
@section('title', 'Chat')
@section('heading', 'Group Chat')
@section('content')
    @include('admin._status')
    <div class="kt-card"><div class="kt-card-content flex flex-col gap-5">
        <div class="max-h-96 overflow-y-auto kt-scrollable-y flex flex-col gap-3">
            @forelse ($messages as $msg)
                <div class="min-w-0">
                    <span class="font-semibold text-mono">{{ optional($msg->sender)->name ?? 'Unknown' }}</span>
                    <span class="text-sm text-secondary-foreground">{{ $msg->created_at?->format('Y-m-d H:i') }}</span>
                    <div class="break-words">{{ $msg->content }}</div>
                </div>
            @empty
                <p class="text-secondary-foreground">No messages yet.</p>
            @endforelse
        </div>
        <form method="POST" action="{{ route('student.chats.messages.send', $chat) }}" class="flex flex-wrap items-center gap-2">@csrf
            <label class="kt-input grow min-w-0">
                <input name="content" placeholder="Type a message…" required>
            </label>
            <button class="kt-btn kt-btn-primary shrink-0">Send</button>
        </form>
        <a href="{{ route('student.chats.index') }}" class="kt-btn kt-btn-light self-start">Back</a>
    </div></div>
@endsection
