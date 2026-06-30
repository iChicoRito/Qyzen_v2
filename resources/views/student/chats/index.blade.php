{{-- H10: student chats — view only (cannot create). --}}
@extends('student.layout')
@section('title', 'Chats')
@section('heading', 'Chats')
@section('content')
    <div class="card"><div class="card-body pt-6">
        <table class="table align-middle table-row-dashed fs-6 gy-3">
            <thead><tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase"><th>Subject</th><th>Messages</th><th class="text-end"></th></tr></thead>
            <tbody class="fw-semibold text-gray-700">
                @forelse ($chats as $chat)
                    <tr>
                        <td>{{ optional($chat->subject)->subject_code }} — {{ optional($chat->subject)->subject_name }}</td>
                        <td>{{ $chat->messages_count }}</td>
                        <td class="text-end"><a href="{{ route('student.chats.show', $chat) }}" class="btn btn-sm btn-light">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted py-5">No chats.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div></div>
@endsection
