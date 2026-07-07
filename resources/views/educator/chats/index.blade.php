@extends('educator.layout')
@section('title', 'Group Chats')
@section('heading', 'Group Chats')
@section('content')
    @include('admin._status')
    <div class="kt-card mb-5">
        <div class="kt-card-content p-5">
            <form method="POST" action="{{ route('educator.chats.store') }}" class="flex flex-wrap gap-3 items-end">
                @csrf
                <div class="flex flex-col gap-1 grow min-w-[220px]">
                    <label class="kt-form-label">Subject</label>
                    <select name="subject_id" class="kt-select">
                        @foreach ($subjects as $s)<option value="{{ $s->id }}" data-section="{{ $s->sections_id }}">{{ $s->subject_code }} — {{ $s->subject_name }}</option>@endforeach
                    </select>
                </div>
                <div class="flex flex-col gap-1 grow min-w-[180px]">
                    <label class="kt-form-label">Section ID</label>
                    <input name="section_id" class="kt-input" placeholder="section id">
                </div>
                <button class="kt-btn kt-btn-primary">Create chat</button>
            </form>
        </div>
    </div>

    <x-data-table id="chats_table" search-placeholder="Search chats" :paginator="$chats">
        <x-slot:head>
            <thead>
                <tr>
                    <th class="min-w-[260px]" data-sort="subject"><span class="kt-table-col"><span class="kt-table-col-label">Subject</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="min-w-[110px]" data-sort="messages"><span class="kt-table-col"><span class="kt-table-col-label">Messages</span><span class="kt-table-col-sort"></span></span></th>
                    <th class="w-[60px]"></th>
                </tr>
            </thead>
        </x-slot:head>
        @forelse ($chats as $chat)
            <tr>
                <td class="text-mono font-medium text-sm">{{ optional($chat->subject)->subject_code }} — {{ optional($chat->subject)->subject_name }}</td>
                <td>{{ $chat->messages_count }}</td>
                <td class="text-center">
                    <x-table-actions :delete="route('educator.chats.destroy', $chat)" confirm="Delete this chat? This cannot be undone.">
                        <div class="kt-menu-item">
                            <a class="kt-menu-link" href="{{ route('educator.chats.show', $chat) }}">
                                <span class="kt-menu-icon"><i class="ki-filled ki-messages"></i></span>
                                <span class="kt-menu-title">Open</span>
                            </a>
                        </div>
                    </x-table-actions>
                </td>
            </tr>
        @empty
            <tr><td colspan="3" class="text-center text-secondary-foreground py-5">No chats.</td></tr>
        @endforelse
    </x-data-table>
@endsection
