{{-- F7: view academic year + its terms. Fragment inside the shared modal under ?modal=1.
     Layout mirrors demo1 public-profile/teams.html team card. --}}
@php $isModal = request()->boolean('modal'); @endphp
@extends($isModal ? 'layouts.fragment' : 'admin.layout')
@section('title', 'Academic Year')
@section('heading', $year->year)
@section('content')
    <div class="kt-card">
        <div class="kt-card-content grid gap-7 py-7.5">
            {{-- Centered identity --}}
            <div class="grid place-items-center gap-4">
                <div class="flex justify-center items-center size-14 rounded-full ring-1 ring-input bg-accent">
                    <i class="ki-filled ki-calendar-tick text-2xl text-muted-foreground"></i>
                </div>
                <div class="grid place-items-center">
                    <span class="text-base font-medium text-mono mb-px">{{ $year->year }}</span>
                    <span class="text-sm text-secondary-foreground text-center">{{ $year->terms->count() }} {{ Str::plural('term', $year->terms->count()) }}</span>
                </div>
            </div>

            {{-- Detail rows --}}
            <div class="grid">
                <div class="flex items-center justify-between flex-wrap mb-2.5 gap-2">
                    <span class="text-xs text-secondary-foreground uppercase">Status</span>
                    <span class="kt-badge kt-badge-outline kt-badge-{{ $year->is_active ? 'success' : 'secondary' }}">{{ $year->is_active ? 'Active' : 'Inactive' }}</span>
                </div>
                <div class="border-t border-input border-dashed mb-3.5"></div>
                <div class="flex items-center justify-between flex-wrap gap-2 mb-2.5">
                    <span class="text-xs text-secondary-foreground uppercase">Terms</span>
                    <span class="kt-badge kt-badge-outline">{{ $year->terms->count() }}</span>
                </div>
                <div class="flex flex-col gap-2">
                    @forelse ($year->terms as $term)
                        <div class="flex items-center justify-between rounded-lg border border-input px-3 py-2">
                            <span class="text-sm text-mono">{{ $term->term_name }}</span>
                            <span class="kt-badge kt-badge-sm kt-badge-outline">{{ $term->semester }}</span>
                        </div>
                    @empty
                        <span class="text-sm text-secondary-foreground">No terms.</span>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="kt-card-footer justify-end gap-2">
            @if ($isModal)
                <button type="button" class="kt-btn kt-btn-outline" data-modal-cancel>Close</button>
                <a href="#" class="kt-btn kt-btn-primary" data-modal-url="{{ route('admin.academic-years.edit', $year) }}" data-modal-target="#form_modal" data-modal-title="Edit academic year">Edit</a>
            @else
                <a href="{{ route('admin.academic-years.index') }}" class="kt-btn kt-btn-outline">Back</a>
                <a href="{{ route('admin.academic-years.edit', $year) }}" class="kt-btn kt-btn-primary">Edit</a>
            @endif
        </div>
    </div>
@endsection
