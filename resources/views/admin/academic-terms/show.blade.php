{{-- F8: view academic term. --}}
@extends('admin.layout')
@section('title', 'Academic Term')
@section('heading', $term->term_name)
@section('content')
    <div class="card"><div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Term</dt><dd class="col-sm-9">{{ $term->term_name }}</dd>
            <dt class="col-sm-3">Semester</dt><dd class="col-sm-9">{{ $term->semester }}</dd>
            <dt class="col-sm-3">Year</dt><dd class="col-sm-9">{{ optional($term->year)->year ?? '—' }}</dd>
            <dt class="col-sm-3">Status</dt><dd class="col-sm-9">{{ $term->is_active ? 'Active' : 'Inactive' }}</dd>
        </dl>
        <div class="mt-4">
            <a href="{{ route('admin.academic-terms.edit', $term) }}" class="btn btn-primary">Edit</a>
            <a href="{{ route('admin.academic-terms.index') }}" class="btn btn-light">Back</a>
        </div>
    </div></div>
@endsection
