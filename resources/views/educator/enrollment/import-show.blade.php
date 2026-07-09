@php
    $isModal = request()->boolean('modal');
    $badge = match ($enrollmentImport->status) {
        'completed' => 'success',
        'failed' => 'destructive',
        'processing' => 'warning',
        default => 'secondary',
    };
@endphp
@extends($isModal ? 'layouts.fragment' : 'educator.layout')
@section('title', 'Enrollment Import')
@section('heading', $enrollmentImport->original_filename)
@section('content')
    <div class="kt-card">
        <div class="kt-card-content grid gap-6 py-7.5">
            <div class="grid place-items-center gap-3">
                <div class="flex justify-center items-center size-14 rounded-full ring-1 ring-input bg-accent/60">
                    <i class="ki-filled ki-file-up text-2xl text-muted-foreground"></i>
                </div>
                <div class="grid place-items-center gap-1.5">
                    <span class="text-base font-medium text-mono text-center" style="word-break:break-word;">{{ $enrollmentImport->original_filename }}</span>
                    <span class="kt-badge kt-badge-outline kt-badge-{{ $badge }}">{{ ucfirst($enrollmentImport->status) }}</span>
                </div>
            </div>

            <div class="grid">
                <div class="flex items-center justify-between flex-wrap mb-2.5 gap-2">
                    <span class="text-xs text-secondary-foreground uppercase">Created</span>
                    <span class="text-sm text-mono text-green-600">{{ $enrollmentImport->created_count }}</span>
                </div>
                <div class="border-t border-input border-dashed"></div>
                <div class="flex items-center justify-between flex-wrap my-2.5 gap-2">
                    <span class="text-xs text-secondary-foreground uppercase">Failed</span>
                    <span class="text-sm text-mono {{ count($enrollmentImport->failed_rows ?? []) > 0 ? 'text-destructive' : '' }}">{{ count($enrollmentImport->failed_rows ?? []) }}</span>
                </div>
                <div class="border-t border-input border-dashed"></div>
                <div class="flex items-center justify-between flex-wrap mt-2.5 gap-2">
                    <span class="text-xs text-secondary-foreground uppercase">Uploaded</span>
                    <span class="text-sm text-secondary-foreground">{{ $enrollmentImport->created_at->format('M j, Y g:i A') }}</span>
                </div>
            </div>

            @if ($enrollmentImport->status === 'failed' && $enrollmentImport->error_message)
                <div class="grid gap-2">
                    <span class="text-xs text-secondary-foreground uppercase">Why it failed</span>
                    <div class="text-sm text-destructive rounded-lg border border-input p-3" style="word-break:break-word;">{{ $enrollmentImport->error_message }}</div>
                </div>
            @elseif (! empty($enrollmentImport->failed_rows))
                <div class="grid gap-2">
                    <span class="text-xs text-secondary-foreground uppercase">Why these rows failed ({{ count($enrollmentImport->failed_rows) }})</span>
                    <div class="flex flex-col gap-2">
                        @foreach ($enrollmentImport->failed_rows as $row)
                            <div class="text-sm rounded-lg border border-input p-3" style="word-break:break-word;">
                                <div class="font-medium text-mono">{{ $row['student_user_id'] ?: 'Row' }}</div>
                                <div class="text-destructive">{{ $row['error'] ?? 'Unknown error.' }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @elseif ($enrollmentImport->status === 'completed')
                <div class="text-sm text-green-600 rounded-lg border border-input p-3">All {{ $enrollmentImport->created_count }} enrollment(s) imported successfully.</div>
            @else
                <div class="text-sm text-secondary-foreground">This import is still {{ $enrollmentImport->status }}. Details will appear when it finishes.</div>
            @endif
        </div>
        <div class="kt-card-footer justify-end gap-2">
            @if ($enrollmentImport->failed_report_path)
                <a href="{{ route('educator.enrollment.import.report', $enrollmentImport) }}" class="kt-btn kt-btn-outline">Download failed rows</a>
            @endif
            @if ($isModal)
                <button type="button" class="kt-btn kt-btn-primary" data-modal-cancel>Close</button>
            @else
                <a href="{{ route('educator.enrollment.index') }}" class="kt-btn kt-btn-primary">Back</a>
            @endif
        </div>
    </div>
@endsection
