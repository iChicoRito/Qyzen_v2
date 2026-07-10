@foreach ($contexts as $subject)
<span class="kt-badge kt-badge-sm kt-badge-outline" data-conversation-context>{{ $subject->subject_code }} · {{ $subject->section?->section_name }}</span>
@endforeach
