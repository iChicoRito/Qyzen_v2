{{-- All-tab notification items. Rendered server-side on load AND returned as an HTML fragment by
     NotificationController@index for 30s polling (so the bell updates without a page refresh). --}}
{{-- Attachment card markup ported verbatim from the "Skylar Frost uploaded 2 attachments" block
     in _demo1_topbar_icons.static-backup.blade.php. --}}
@php
    $profileMediaUrl = fn (?string $path) => $path ? \Illuminate\Support\Facades\Storage::disk('profile_media')->url($path) : null;
    $fileIcon = fn (string $ext) => match (strtolower($ext)) {
        'pdf' => 'pdf.svg',
        'ppt', 'pptx', 'ppsx' => 'powerpoint.svg',
        'doc', 'docx' => 'word.svg',
        'xls', 'xlsx', 'csv' => 'xls.svg',
        'rtf' => 'text.svg',
        default => 'text.svg',
    };
    $humanSize = function (?int $bytes) {
        if (!$bytes) return null;
        if ($bytes < 1024) return $bytes.'b';
        $kb = $bytes / 1024;
        return $kb < 1024 ? number_format($kb, 1).'kb' : number_format($kb / 1024, 1).'MB';
    };
    $downloadIconSvg = <<<'SVG'
        <svg fill="none" height="14" viewbox="0 0 14 14" width="14" xmlns="http://www.w3.org/2000/svg">
         <path clip-rule="evenodd" d="M6.63821 2.60467C4.81926 2.60467 3.32474 3.99623 3.16201 5.77252C3.1386 6.02803 2.92413 6.22253 2.66871 6.22227C1.74915 6.22149 0.976744 6.9868 0.976744 7.90442C0.976744 8.83344 1.72988 9.58657 2.65891 9.58657H3.09302C3.36274 9.58657 3.5814 9.80523 3.5814 10.0749C3.5814 10.3447 3.36274 10.5633 3.09302 10.5633H2.65891C1.19044 10.5633 0 9.37292 0 7.90442C0 6.58614 0.986948 5.48438 2.24496 5.27965C2.62863 3.20165 4.44941 1.62793 6.63821 1.62793C8.26781 1.62793 9.69282 2.50042 10.4729 3.80193C12.3411 3.72829 14 5.2564 14 7.18091C14 8.93508 12.665 10.3769 10.9552 10.5466C10.6868 10.5733 10.4476 10.3773 10.421 10.1089C10.3943 9.84052 10.5903 9.60135 10.8587 9.57465C12.0739 9.45406 13.0233 8.42802 13.0233 7.18091C13.0233 5.74002 11.6905 4.59666 10.2728 4.79968C10.0642 4.82957 9.85672 4.72382 9.76028 4.53181C9.18608 3.38796 8.00318 2.60467 6.63821 2.60467Z" fill="#99A1B7" fill-rule="evenodd"></path>
         <path clip-rule="evenodd" d="M6.99909 8.01611L8.28162 9.29864C8.47235 9.48937 8.78158 9.48937 8.97231 9.29864C9.16303 9.10792 9.16303 8.79874 8.97231 8.60802L7.57465 7.2103C7.25675 6.89247 6.74143 6.89247 6.42353 7.2103L5.02585 8.60802C4.83513 8.79874 4.83513 9.10792 5.02585 9.29864C5.21657 9.48937 5.5258 9.48937 5.71649 9.29864L6.99909 8.01611Z" fill="#99A1B7" fill-rule="evenodd"></path>
         <path clip-rule="evenodd" d="M7.00009 12.372C7.2698 12.372 7.48846 12.1533 7.48846 11.8836V7.97665C7.48846 7.70694 7.2698 7.48828 7.00009 7.48828C6.73038 7.48828 6.51172 7.70694 6.51172 7.97665V11.8836C6.51172 12.1533 6.73038 12.372 7.00009 12.372Z" fill="#99A1B7" fill-rule="evenodd"></path>
        </svg>
        SVG;
@endphp
@forelse (($notifications ?? []) as $n)
@php
    $isMaterial = \Illuminate\Support\Str::startsWith($n->event_type, 'learning_material');
    $isAnnouncement = $n->event_type === 'announcement_created';
    $files = $n->metadata['files'] ?? null;
@endphp
<a class="flex grow gap-2.5 px-5 py-3 {{ $n->is_read ? '' : 'bg-primary/5' }}" href="{{ route('notifications.open', $n, false) }}" data-kt-notif-item>
 <div class="kt-avatar size-8">
  @if ($n->actor && $n->actor->profile_picture)
  <div class="kt-avatar-image">
   <img alt="avatar" src="{{ $profileMediaUrl($n->actor->profile_picture) }}"/>
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
   <div class="flex items-start justify-between gap-3 text-sm font-medium mb-px" @if ($isAnnouncement) data-notification-meta-row @endif>
    <div class="flex flex-wrap items-center gap-1.5">
     <span class="text-mono font-semibold">{{ $n->actor?->name ?? 'System' }}</span>
     @if ($isAnnouncement)
     <span class="kt-badge kt-badge-sm kt-badge-primary kt-badge-outline">Educator</span>
     @endif
     <span class="text-secondary-foreground">{{ $n->title }}</span>
    </div>
    <time class="text-xs font-medium text-muted-foreground shrink-0" @if ($isAnnouncement) data-notification-timestamp @endif>{{ $n->created_at?->diffForHumans() }}</time>
   </div>
   @if ($n->subject)
   <span class="flex items-center text-xs font-medium text-muted-foreground">
    {{ $n->subject->subject_name ?? $n->subject->subject_code }}
   </span>
   @endif
  </div>
  @if ($isMaterial && $files)
  @foreach ($files as $f)
  <div class="kt-card shadow-none flex items-center justify-between flex-row gap-1.5 p-2.5 rounded-lg bg-muted/70">
   <div class="flex items-center gap-1.5">
    <img class="h-6" src="{{ asset('metronic-tailwind-html-demos/dist/assets/media/file-types/'.$fileIcon($f['file_extension'] ?? '')) }}"/>
    <div class="flex flex-col gap-0.5">
     <span class="hover:text-primary font-medium text-secondary-foreground text-xs">{{ $f['file_name'] }}</span>
     <span class="font-medium text-muted-foreground text-xs">{{ $humanSize($f['file_size'] ?? null) }}</span>
    </div>
   </div>
   <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost">{!! $downloadIconSvg !!}</button>
  </div>
  @endforeach
  @elseif ($isMaterial)
  {{-- Older notifications predating the files[] metadata: fall back to the count-only chip. --}}
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
