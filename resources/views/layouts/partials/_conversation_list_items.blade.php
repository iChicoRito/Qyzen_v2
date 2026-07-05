{{-- Task 30: private-message conversation previews. Shared by the Inbox tab and the chat-drawer
     list state — same data shape (avatar, other-party name, last message, unread indicator).
     Rendered server-side on load AND returned as an HTML fragment for polling. Each item is
     data-conversation-id driven JS (see _demo1_topbar_icons.blade.php) that opens the chat
     drawer straight to that thread instead of navigating anywhere. --}}
@forelse (($rows ?? []) as $row)
@php
    $other = $row->other;
    $last = $row->lastMessage;
    $unread = $row->unreadCount > 0;
@endphp
<a href="#" class="flex grow gap-2.5 px-5 py-3 {{ $unread ? 'bg-primary/5' : '' }}" data-conversation-item data-conversation-id="{{ $row->conversation->id }}">
 <div class="kt-avatar size-8">
  @if ($other->profile_picture)
  <div class="kt-avatar-image">
   <img alt="avatar" src="{{ asset('storage/'.$other->profile_picture) }}"/>
  </div>
  @else
  <span class="inline-flex items-center justify-center size-8 rounded-full bg-primary/10 text-primary text-xs font-semibold">{{ strtoupper(substr($other->given_name ?? '?', 0, 1)) }}</span>
  @endif
 </div>
 <div class="flex flex-col gap-1 grow min-w-0">
  <div class="flex items-center justify-between gap-2">
   <span class="text-sm font-medium text-mono {{ $unread ? 'font-semibold' : '' }}" data-conversation-name>{{ $other->name }}</span>
   <span class="text-xs font-medium text-muted-foreground shrink-0">{{ $row->lastActivityAt?->diffForHumans() }}</span>
  </div>
  <div class="flex items-center gap-1.5">
   @if ($other->user_id)
   <span class="text-xs font-medium text-muted-foreground">{{ $other->user_id }}</span>
   @endif
   <span class="kt-badge kt-badge-sm kt-badge-outline {{ $other->user_type === 'educator' ? 'kt-badge-primary' : 'kt-badge-info' }}">{{ ucfirst($other->user_type) }}</span>
  </div>
  <div class="flex items-center justify-between gap-2">
   <span class="text-xs font-medium text-secondary-foreground truncate">
    {{ $last ? $last->displayContent() : 'Say hello!' }}
   </span>
   @if ($unread)
   <span class="shrink-0 flex items-center justify-center size-[18px] rounded-full bg-primary text-primary-foreground text-[10px] font-semibold leading-none">{{ $row->unreadCount > 9 ? '9+' : $row->unreadCount }}</span>
   @endif
  </div>
 </div>
</a>
<div class="border-b border-b-border"></div>
@empty
<div class="flex flex-col items-center justify-center text-center gap-2 py-10 px-5">
 <i class="ki-filled ki-messages text-2xl text-muted-foreground"></i>
 <span class="text-sm font-medium text-secondary-foreground">No conversations yet</span>
</div>
@endforelse
