@extends('educator.layout')
@section('title', 'Group Chats')
@section('heading', 'Group Chats')
@section('content')
    @include('admin._status')
    <div class="card mb-5"><div class="card-body">
        <form method="POST" action="{{ route('educator.chats.store') }}" class="row g-3 align-items-end">@csrf
            <div class="col-md-5"><label class="form-label required">Subject</label>
                <select name="subject_id" class="form-select">
                    @foreach ($subjects as $s)<option value="{{ $s->id }}" data-section="{{ $s->sections_id }}">{{ $s->subject_code }} — {{ $s->subject_name }}</option>@endforeach
                </select></div>
            <div class="col-md-5"><label class="form-label required">Section ID</label>
                <input name="section_id" class="form-control" placeholder="section id"></div>
            <div class="col-md-2"><button class="btn btn-primary w-100">Create chat</button></div>
        </form>
    </div></div>

    <div class="card"><div class="card-body pt-6">
        <table class="table align-middle table-row-dashed fs-6 gy-3">
            <thead><tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase"><th>Subject</th><th>Messages</th><th class="text-end">Actions</th></tr></thead>
            <tbody class="fw-semibold text-gray-700">
                @forelse ($chats as $chat)
                    <tr>
                        <td>{{ optional($chat->subject)->subject_code }} — {{ optional($chat->subject)->subject_name }}</td>
                        <td>{{ $chat->messages_count }}</td>
                        <td class="text-end">
                            <a href="{{ route('educator.chats.show', $chat) }}" class="btn btn-sm btn-light">Open</a>
                            <form method="POST" action="{{ route('educator.chats.destroy', $chat) }}" class="d-inline" onsubmit="return confirm('Delete this chat?')">@csrf @method('DELETE')<button class="btn btn-sm btn-light-danger">Delete</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted py-5">No chats.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div></div>
@endsection
