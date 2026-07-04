{{-- All-tab notification items. Rendered server-side on load AND returned as an HTML fragment by
     NotificationController@index for 30s polling (so the bell updates without a page refresh). --}}
@forelse (($notifications ?? []) as $n)
@php $isMaterial = \Illuminate\Support\Str::startsWith($n->event_type, 'learning_material'); @endphp
<a class="flex grow gap-2.5 px-5 py-1 {{ $n->is_read ? '' : 'bg-primary/5' }}" href="{{ $n->link_path ?? '#' }}" data-kt-notif-item>
 <div class="kt-avatar size-8">
  @if ($n->actor && $n->actor->profile_picture)
  <div class="kt-avatar-image">
   <img alt="avatar" src="{{ asset('storage/'.$n->actor->profile_picture) }}"/>
  </div>
  @else
  <span class="inline-flex items-center justify-center size-8 rounded-full bg-primary/10 text-primary text-xs font-semibold">{{ strtoupper(substr($n->actor->given_name ?? 'S', 0, 1)) }}</span>
  @endif
  <div class="kt-avatar-indicator -end-2 -bottom-2">
   <div class="kt-avatar-status {{ $n->is_read ? 'kt-avatar-status-offline' : 'kt-avatar-status-online' }} size-2.5"></div>
  </div>
 </div>
 <div class="flex flex-col gap-3.5 grow">
  <div class="flex flex-col gap-1">
   <div class="text-sm font-medium mb-px">
    <span class="text-mono font-semibold">{{ $n->actor?->name ?? 'System' }}</span>
    <span class="text-secondary-foreground">{{ $n->title }}</span>
   </div>
   <span class="flex items-center text-xs font-medium text-muted-foreground">
    {{ $n->created_at?->diffForHumans() }}
    @if ($n->subject)
    <span class="rounded-full size-1 bg-mono/30 mx-1.5"></span>
    {{ $n->subject->subject_name ?? $n->subject->subject_code }}
    @endif
   </span>
  </div>
  @if ($isMaterial)
  @php $fileCount = (int) ($n->metadata['file_count'] ?? 1); @endphp
  <div class="kt-card shadow-none flex items-center flex-row gap-1.5 p-2.5 rounded-lg bg-muted/70">
   <div class="flex items-center justify-center w-[26px] h-[30px] shrink-0 bg-background rounded-sm border border-border">
    <i class="ki-filled ki-document text-base text-muted-foreground"></i>
   </div>
   <span class="font-medium text-secondary-foreground text-xs me-1">{{ $fileCount }} file{{ $fileCount === 1 ? '' : 's' }}</span>
   @if ($n->section)
   <span class="font-medium text-muted-foreground text-xs">{{ $n->section->section_name }}</span>
   @endif
  </div>
  @elseif ($n->subject || $n->section || $n->assessment)
  <div class="flex flex-wrap gap-2.5">
   @if ($n->subject)
   <span class="kt-badge kt-badge-sm kt-badge-info kt-badge-outline">{{ $n->subject->subject_code ?? $n->subject->subject_name }}</span>
   @endif
   @if ($n->section)
   <span class="kt-badge kt-badge-sm kt-badge-secondary kt-badge-outline">{{ $n->section->section_name }}</span>
   @endif
   @if ($n->assessment)
   <span class="kt-badge kt-badge-sm kt-badge-warning kt-badge-outline">{{ $n->assessment->assessment_code }}</span>
   @endif
  </div>
  @endif
 </div>
</a>
<div class="border-b border-b-border"></div>
@empty
<div class="flex flex-col items-center justify-center text-center gap-2 py-10 px-5">
 <i class="ki-filled ki-notification-status text-2xl text-muted-foreground"></i>
 <span class="text-sm font-medium text-secondary-foreground">No notifications yet</span>
</div>
@endforelse
