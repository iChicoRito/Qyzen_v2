{{-- F7: view academic year + its terms. --}}
@extends('admin.layout')
@section('title', 'Academic Year')
@section('heading', $year->year)
@section('content')
    <div class="card"><div class="card-body">
        <dl class="row mb-4">
            <dt class="col-sm-3">Year</dt><dd class="col-sm-9">{{ $year->year }}</dd>
            <dt class="col-sm-3">Status</dt><dd class="col-sm-9">{{ $year->is_active ? 'Active' : 'Inactive' }}</dd>
        </dl>
        <h4>Terms ({{ $year->terms->count() }})</h4>
        <ul class="mb-4">
            @forelse ($year->terms as $term)
                <li>{{ $term->term_name }} — {{ $term->semester }}</li>
            @empty
                <li class="text-muted">No terms.</li>
            @endforelse
        </ul>
        <a href="{{ route('admin.academic-years.edit', $year) }}" class="btn btn-primary">Edit</a>
        <a href="{{ route('admin.academic-years.index') }}" class="btn btn-light">Back</a>
    </div></div>
@endsection
