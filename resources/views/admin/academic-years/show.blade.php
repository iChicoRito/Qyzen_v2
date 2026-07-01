{{-- F7: view academic year + its terms. --}}
@extends('admin.layout')
@section('title', 'Academic Year')
@section('heading', $year->year)
@section('content')
    <div class="kt-card"><div class="kt-card-content p-5">
        <dl class="grid grid-cols-1 sm:grid-cols-[160px_1fr] gap-y-3 gap-x-4 text-sm mb-5">
            <dt class="text-secondary-foreground">Year</dt><dd class="text-mono">{{ $year->year }}</dd>
            <dt class="text-secondary-foreground">Status</dt><dd class="text-mono">{{ $year->is_active ? 'Active' : 'Inactive' }}</dd>
        </dl>
        <h4 class="text-sm font-semibold text-mono mb-2.5">Terms ({{ $year->terms->count() }})</h4>
        <ul class="list-disc ps-5 mb-5 text-sm">
            @forelse ($year->terms as $term)
                <li>{{ $term->term_name }} — {{ $term->semester }}</li>
            @empty
                <li class="text-secondary-foreground list-none">No terms.</li>
            @endforelse
        </ul>
        <div class="flex gap-2">
            <a href="{{ route('admin.academic-years.edit', $year) }}" class="kt-btn kt-btn-primary">Edit</a>
            <a href="{{ route('admin.academic-years.index') }}" class="kt-btn kt-btn-outline">Back</a>
        </div>
    </div></div>
@endsection
