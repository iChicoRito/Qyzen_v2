{{-- H9: student materials — enrollment-gated. --}}
@extends('student.layout')
@section('title', 'Materials')
@section('heading', 'Learning Materials')
@section('content')
    <div class="card"><div class="card-body pt-6">
        <table class="table align-middle table-row-dashed fs-6 gy-3">
            <thead><tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase"><th>File</th><th>Subject</th><th>Type</th><th>Updated</th><th class="text-end"></th></tr></thead>
            <tbody class="fw-semibold text-gray-700">
                @forelse ($groups as $rows)
                    @foreach ($rows as $m)
                        <tr>
                            <td>{{ $m->file_name }}</td>
                            <td>{{ optional($m->subject)->subject_code }}</td>
                            <td>{{ strtoupper($m->file_extension) }}</td>
                            <td>{{ $m->updated_at?->format('Y-m-d') }}</td>
                            <td class="text-end"><a href="{{ route('student.materials.download', $m) }}" class="btn btn-sm btn-primary">Download</a></td>
                        </tr>
                    @endforeach
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-5">No materials available.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div></div>
@endsection
