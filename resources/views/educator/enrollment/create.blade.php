@extends(request()->boolean('modal') ? 'layouts.fragment' : 'educator.layout')
@section('title', 'Enroll Students')
@section('heading', 'Enroll Students')
@section('content')
    @include('admin._status')
    <div class="kt-card"><div class="kt-card-content p-5">
        @if ($subjects->isEmpty())
            <div class="kt-alert kt-alert-warning">Create a subject first.</div>
        @else
            <form method="POST" action="{{ route('educator.enrollment.store') }}">@csrf
                <div class="grid grid-cols-1 gap-5">
                    <div class="flex flex-col gap-1">
                        <label class="kt-form-label">Students</label>
                        <select name="student_ids[]" class="kt-select" multiple
                                data-kt-select="true"
                                data-kt-select-multiple="true"
                                data-kt-select-enable-search="true"
                                data-kt-select-placeholder="Select students…"
                                data-kt-select-search-placeholder="Search students…">
                            @foreach ($students as $s)
                                <option value="{{ $s->id }}">{{ $s->user_id }} — {{ $s->name }}</option>
                            @endforeach
                        </select>
                        @error('student_ids')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="kt-form-label">Subjects</label>
                        <select name="subject_ids[]" class="kt-select" multiple
                                data-kt-select="true"
                                data-kt-select-multiple="true"
                                data-kt-select-enable-search="true"
                                data-kt-select-placeholder="Select subjects…"
                                data-kt-select-search-placeholder="Search subjects…">
                            @foreach ($subjects as $sub)
                                <option value="{{ $sub->id }}">{{ $sub->subject_code }} — {{ $sub->subject_name }} ({{ optional($sub->section)->section_name ?? 'No section' }})</option>
                            @endforeach
                        </select>
                        @error('subject_ids')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="kt-form-label">Status</label>
                        <select name="is_active" class="kt-select"><option value="1">Active</option><option value="0">Inactive</option></select>
                    </div>
                </div>
                <div class="flex gap-2 mt-5"><button class="kt-btn kt-btn-primary">Enroll</button>
                    <a href="{{ route('educator.enrollment.index') }}" class="kt-btn kt-btn-outline" data-modal-cancel>Cancel</a></div>
            </form>
        @endif
    </div></div>
@endsection
