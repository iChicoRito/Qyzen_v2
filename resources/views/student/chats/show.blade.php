{{-- H10: student chat thread — view + send (cannot create/delete). --}}
@extends('student.layout')
@section('title', 'Chat')
@section('heading', 'Group Chat')
@section('content')
    @include('admin._status')
    <div class="card"><div class="card-body">
        <div class="mb-5" style="max-height:400px;overflow-y:auto">
            @forelse ($messages as $msg)
                <div class="mb-3">
                    <span class="fw-bold">{{ optional($msg->sender)->name ?? 'Unknown' }}</span>
                    <span class="text-muted fs-7">{{ $msg->created_at?->format('Y-m-d H:i') }}</span>
                    <div>{{ $msg->content }}</div>
                </div>
            @empty
                <p class="text-muted">No messages yet.</p>
            @endforelse
        </div>
        <form method="POST" action="{{ route('student.chats.messages.send', $chat) }}" class="d-flex gap-2">@csrf
            <input name="content" class="form-control" placeholder="Type a message…" required>
            <button class="btn btn-primary">Send</button>
        </form>
        <a href="{{ route('student.chats.index') }}" class="btn btn-light mt-3">Back</a>
    </div></div>
@endsection
