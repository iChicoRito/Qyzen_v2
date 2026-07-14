@php
    $active = $imports->contains(fn ($i) => in_array($i->status, ['queued', 'processing'], true));
@endphp
<div data-import-timeline data-active="{{ $active ? '1' : '0' }}">
    @if ($imports->isNotEmpty())
        <div class="kt-card">
            <div class="kt-card-header flex items-center justify-between gap-2">
                <h3 class="kt-card-title">Recent enrollment uploads</h3>
                <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost shrink-0"
                        data-kt-toggle="#enrollment_layout" data-kt-toggle-class="kt-timeline-collapsed" title="Collapse panel">
                    <i class="ki-filled ki-arrow-left qz-timeline-toggle-icon"></i>
                </button>
            </div>
            <div class="kt-card-content">
                <div class="flex flex-col">
                    @foreach ($imports as $import)
                        @php
                            $failedCount = count($import->failed_rows ?? []);
                            [$icon, $tone] = match ($import->status) {
                                'completed' => $failedCount > 0
                                    ? ['ki-cross-circle', 'text-destructive']
                                    : ['ki-check-circle', 'text-green-600'],
                                'failed' => ['ki-cross-circle', 'text-destructive'],
                                'processing' => ['ki-loading', ''],
                                'cancelled' => ['ki-cross-circle', 'text-secondary-foreground'],
                                default => ['ki-time', 'text-yellow-600'],
                            };
                        @endphp
                        <div class="flex items-start relative">
                            @unless ($loop->last)
                                <div class="w-9 start-0 top-9 absolute bottom-0 rtl:-translate-x-1/2 translate-x-1/2 border-s border-s-input"></div>
                            @endunless
                            <div class="flex items-center justify-center shrink-0 rounded-full bg-accent/60 border border-input size-9 text-secondary-foreground">
                                <i class="ki-filled {{ $icon }} text-base {{ $tone }}"></i>
                            </div>
                            <div class="ps-2.5 {{ $loop->last ? '' : 'mb-7' }} text-base grow">
                                <div class="flex flex-col">
                                    <div class="text-sm {{ $tone ?: 'text-foreground' }}">
                                        <button type="button" class="font-medium text-mono text-start hover:text-primary"
                                            data-modal-url="{{ route('educator.enrollment.imports.show', $import) }}"
                                            data-modal-target="#form_modal"
                                            data-modal-title="{{ $import->original_filename }}">{{ $import->original_filename }}</button>
                                        @switch($import->status)
                                            @case('completed')
                                                imported — {{ $import->created_count }} created, {{ $failedCount }} failed.
                                                @break
                                            @case('processing')
                                                is processing.
                                                @break
                                            @case('failed')
                                                failed to import.
                                                @break
                                            @case('cancelled')
                                                was cancelled.
                                                @break
                                            @default
                                                queued for import.
                                        @endswitch
                                    </div>
                                    <span class="text-xs text-secondary-foreground">{{ $import->created_at->format('M j, Y g:i A') }}</span>
                                    @if ($import->status === 'failed' && $import->error_message)
                                        <span class="text-xs text-destructive">{{ $import->error_message }}</span>
                                    @endif
                                </div>
                                @if ($import->failed_report_path)
                                    <a href="{{ route('educator.enrollment.import.report', $import) }}" class="kt-link kt-link-underlined kt-link-dashed text-sm mt-1">Download failed rows</a>
                                @endif
                                @if ($import->status === 'queued')
                                    <form method="POST" action="{{ route('educator.enrollment.imports.cancel', $import) }}" class="mt-2" data-confirm="Cancel this queued enrollment import?" data-confirm-title="Cancel import?">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="kt-btn kt-btn-sm kt-btn-outline">Cancel</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="kt-card-footer">
                <form method="POST" action="{{ route('educator.enrollment.imports.clear') }}" class="w-full" data-confirm="Clear this import history? Enrollment records will not be changed." data-confirm-title="Clear history?">
                    @csrf @method('DELETE')
                    <button type="submit" class="kt-btn kt-btn-outline w-full justify-center">Clear History</button>
                </form>
            </div>
        </div>
    @else
        <div class="kt-card">
            <div class="kt-card-content text-center text-sm text-secondary-foreground py-8">No enrollment uploads yet.</div>
        </div>
    @endif
</div>
