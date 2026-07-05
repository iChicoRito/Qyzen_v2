@php
    $active = $imports->contains(fn ($i) => in_array($i->status, ['queued', 'processing'], true));
@endphp
<div data-import-timeline data-active="{{ $active ? '1' : '0' }}">
    @if ($imports->isNotEmpty())
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Recent enrollment uploads</h3>
            </div>
            <div class="kt-card-content">
                <div class="flex flex-col">
                    @foreach ($imports as $import)
                        @php
                            [$icon, $tone] = match ($import->status) {
                                'completed' => ['ki-check-circle', 'text-green-600'],
                                'failed' => ['ki-cross-circle', 'text-destructive'],
                                'processing' => ['ki-loading', ''],
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
                                                imported {{ $import->created_count }} enrollment(s).
                                                @break
                                            @case('processing')
                                                is processing.
                                                @break
                                            @case('failed')
                                                failed to import.
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
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @else
        <div class="kt-card">
            <div class="kt-card-content text-center text-sm text-secondary-foreground py-8">No enrollment uploads yet.</div>
        </div>
    @endif
</div>
