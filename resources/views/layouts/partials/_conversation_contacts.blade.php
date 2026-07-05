{{-- Task 30: "New message" picker — the people the signed-in user may start a conversation with
     (their active-enrollment counterparties). Each row shows the person's number + role badge.
     Educators also get a subject/section filter (client-side, via data-subject-ids on each row).
     Clicking a row find-or-creates the shared thread and opens it (see the compose handler in
     _demo1_topbar_icons.blade.php). --}}
@if (!empty($subjects) && count($subjects))
<div class="px-5 py-2.5 border-b border-b-border">
 <select class="kt-select" id="chat_drawer_subject_filter" data-kt-select="true" data-kt-select-enable-search="true" data-kt-select-search-placeholder="Search subjects…" data-kt-select-dropdown-container="body">
  <option value="">All subjects &amp; sections</option>
  @foreach ($subjects as $sub)
  <option value="{{ $sub->id }}">{{ $sub->subject_code }}@if ($sub->section) &middot; {{ $sub->section->section_name }}@endif</option>
  @endforeach
 </select>
</div>
@endif
@forelse (($contacts ?? []) as $row)
@php $u = $row->user; @endphp
<a href="#" class="flex items-center grow gap-2.5 px-5 py-2" data-contact-item data-contact-id="{{ $u->id }}" data-contact-search="{{ strtolower($u->name) }}" data-subject-ids="{{ implode(',', $row->subjectIds) }}">
 <div class="kt-avatar size-8">
  @if ($u->profile_picture)
  <div class="kt-avatar-image">
   <img alt="avatar" src="{{ asset('storage/'.$u->profile_picture) }}"/>
  </div>
  @else
  <span class="inline-flex items-center justify-center size-8 rounded-full bg-primary/10 text-primary text-xs font-semibold">{{ strtoupper(substr($u->given_name ?? '?', 0, 1)) }}</span>
  @endif
 </div>
 <div class="flex flex-col gap-0.5 grow min-w-0">
  <span class="text-sm font-medium text-mono" data-contact-name>{{ $u->name }}</span>
  <div class="flex items-center gap-1.5">
   @if ($u->user_id)
   <span class="text-xs font-medium text-muted-foreground">{{ $u->user_id }}</span>
   @endif
   <span class="kt-badge kt-badge-sm kt-badge-outline {{ $u->user_type === 'educator' ? 'kt-badge-primary' : 'kt-badge-info' }}">{{ ucfirst($u->user_type) }}</span>
  </div>
 </div>
</a>
<div class="border-b border-b-border"></div>
@empty
<div class="flex flex-col items-center justify-center text-center gap-2 py-10 px-5">
 <i class="ki-filled ki-messages text-2xl text-muted-foreground"></i>
 <span class="text-sm font-medium text-secondary-foreground">No one available to message</span>
</div>
@endforelse
