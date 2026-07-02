{{-- F8: view academic term. Fragment inside the shared modal under ?modal=1.
     Layout mirrors demo1 public-profile/teams.html team card. --}}
@php $isModal = request()->boolean('modal'); @endphp
@extends($isModal ? 'layouts.fragment' : 'admin.layout')
@section('title', 'Academic Term')
@section('heading', $term->term_name)
@section('content')
    <div class="kt-card">
        <div class="kt-card-content grid gap-7 py-7.5">
            {{-- Centered identity --}}
            <div class="grid place-items-center gap-4">
                <div class="flex justify-center items-center size-14 rounded-full ring-1 ring-input bg-accent">
                    <i class="ki-filled ki-calendar text-2xl text-muted-foreground"></i>
                </div>
                <div class="grid place-items-center">
                    <span class="text-base font-medium text-mono mb-px">{{ $term->term_name }}</span>
                    <span class="text-sm text-secondary-foreground text-center">{{ $term->semester }} · {{ optional($term->year)->year ?? 'No year' }}</span>
                </div>
            </div>

            {{-- Detail rows --}}
            <div class="grid">
                <div class="flex items-center justify-between flex-wrap mb-2.5 gap-2">
                    <span class="text-xs text-secondary-foreground uppercase">Semester</span>
                    <span class="text-sm text-mono">{{ $term->semester }}</span>
                </div>
                <div class="border-t border-input border-dashed"></div>
                <div class="flex items-center justify-between flex-wrap my-2.5 gap-2">
                    <span class="text-xs text-secondary-foreground uppercase">Academic year</span>
                    <span class="text-sm text-mono">{{ optional($term->year)->year ?? '—' }}</span>
                </div>
                <div class="border-t border-input border-dashed"></div>
                <div class="flex items-center justify-between flex-wrap mt-2.5 gap-2">
                    <span class="text-xs text-secondary-foreground uppercase">Status</span>
                    <span class="kt-badge kt-badge-outline kt-badge-{{ $term->is_active ? 'success' : 'secondary' }}">{{ $term->is_active ? 'Active' : 'Inactive' }}</span>
                </div>
            </div>
        </div>
        <div class="kt-card-footer justify-end gap-2">
            @if ($isModal)
                <button type="button" class="kt-btn kt-btn-outline" data-modal-cancel>Close</button>
                <a href="#" class="kt-btn kt-btn-primary" data-modal-url="{{ route('admin.academic-terms.edit', $term) }}" data-modal-target="#form_modal" data-modal-title="Edit academic term">Edit</a>
            @else
                <a href="{{ route('admin.academic-terms.index') }}" class="kt-btn kt-btn-outline">Back</a>
                <a href="{{ route('admin.academic-terms.edit', $term) }}" class="kt-btn kt-btn-primary">Edit</a>
            @endif
        </div>
    </div>
@endsection
