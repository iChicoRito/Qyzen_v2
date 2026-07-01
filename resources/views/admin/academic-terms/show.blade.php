{{-- F8: view academic term. --}}
@extends('admin.layout')
@section('title', 'Academic Term')
@section('heading', $term->term_name)
@section('content')
    <div class="kt-card"><div class="kt-card-content p-5">
        <dl class="grid grid-cols-1 sm:grid-cols-[160px_1fr] gap-y-3 gap-x-4 text-sm mb-0">
            <dt class="text-secondary-foreground">Term</dt><dd class="text-mono">{{ $term->term_name }}</dd>
            <dt class="text-secondary-foreground">Semester</dt><dd class="text-mono">{{ $term->semester }}</dd>
            <dt class="text-secondary-foreground">Year</dt><dd class="text-mono">{{ optional($term->year)->year ?? '—' }}</dd>
            <dt class="text-secondary-foreground">Status</dt><dd class="text-mono">{{ $term->is_active ? 'Active' : 'Inactive' }}</dd>
        </dl>
        <div class="flex gap-2 mt-5">
            <a href="{{ route('admin.academic-terms.edit', $term) }}" class="kt-btn kt-btn-primary">Edit</a>
            <a href="{{ route('admin.academic-terms.index') }}" class="kt-btn kt-btn-outline">Back</a>
        </div>
    </div></div>
@endsection
