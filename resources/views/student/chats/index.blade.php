{{-- H10: student chats — view only (cannot create). --}}
@extends('student.layout')
@section('title', 'Chats')
@section('heading', 'Chats')
@section('content')
    <x-data-table id="student_chats_table" search-placeholder="Search chats">
        <x-slot:head>
            <thead>
                <tr>
                    <th class="min-w-[260px]"><span class="kt-table-col"><span class="kt-table-col-label">Subject</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[110px]"><span class="kt-table-col"><span class="kt-table-col-label">Messages</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="w-[120px] text-end">Action</th>
                </tr>
            </thead>
        </x-slot:head>
        @forelse ($chats as $chat)
            <tr>
                <td class="text-mono font-medium text-sm">{{ optional($chat->subject)->subject_code }} — {{ optional($chat->subject)->subject_name }}</td>
                <td>{{ $chat->messages_count }}</td>
                <td class="text-end">
                    <a href="{{ route('student.chats.show', $chat) }}" class="kt-btn kt-btn-sm kt-btn-outline">Open</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="3" class="text-center text-secondary-foreground py-5">No chats.</td></tr>
        @endforelse
    </x-data-table>
@endsection
