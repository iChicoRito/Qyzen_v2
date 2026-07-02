@extends(request()->boolean('modal') ? 'layouts.fragment' : 'educator.layout')
@section('title', 'Upload Materials')
@section('heading', 'Upload Materials')
@section('content')
    @include('admin._status')
    <div class="kt-card"><div class="kt-card-content p-5">
        @if ($subjects->isEmpty() || $sections->isEmpty())
            <div class="kt-alert kt-alert-warning">Create a subject and section first.</div>
        @else
            <form method="POST" action="{{ route('educator.materials.store') }}" enctype="multipart/form-data">@csrf
                <div class="grid grid-cols-2 gap-5">
                    <div class="flex flex-col gap-1">
                        <label class="kt-form-label">Subject</label>
                        <select name="subject_id" class="kt-select">
                            @foreach ($subjects as $s)<option value="{{ $s->id }}">{{ $s->subject_code }} — {{ $s->subject_name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="kt-form-label">Section</label>
                        <select name="section_id" class="kt-select">
                            @foreach ($sections as $s)<option value="{{ $s->id }}">{{ $s->section_name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="flex flex-col gap-1 col-span-2">
                        <label class="kt-form-label">Files</label>
                        <input type="file" name="files[]" class="kt-input" multiple required>
                        @error('files')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
                        @error('files.*')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="flex gap-2 mt-5"><button class="kt-btn kt-btn-primary">Upload</button>
                    <a href="{{ route('educator.materials.index') }}" class="kt-btn kt-btn-outline" data-modal-cancel>Cancel</a></div>
            </form>
        @endif
    </div></div>
@endsection
