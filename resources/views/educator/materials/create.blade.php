@extends(request()->boolean('modal') ? 'layouts.fragment' : 'educator.layout')
@section('title', 'Upload Materials')
@section('heading', 'Upload Materials')
@section('content')
    @include('admin._status')
    <div class="kt-card"><div class="kt-card-content p-5">
        @if ($subjects->isEmpty())
            <div class="kt-alert kt-alert-warning">Create a subject and section first.</div>
        @else
            <form method="POST" action="{{ route('educator.materials.store') }}" enctype="multipart/form-data">@csrf
                <div class="flex flex-col gap-5">
                    <div class="flex flex-col gap-1.5">
                        <label class="kt-form-label">Assign To</label>
                        <details class="rounded-lg border border-border" @if($errors->has('subject_ids')) open @endif>
                            <summary class="flex items-center justify-between gap-2 px-4 py-3 cursor-pointer select-none list-none [&::-webkit-details-marker]:hidden">
                                <span class="text-sm text-mono" data-subject-summary data-subject-summary-default="Select subject and section options">
                                    {{ count(old('subject_ids', [])) ? count(old('subject_ids', [])) . ' selected' : 'Select subject and section options' }}
                                </span>
                                <i class="ki-filled ki-down text-sm text-muted-foreground"></i>
                            </summary>
                            <div class="grid grid-cols-1 gap-2.5 p-3 pt-0 max-h-72 overflow-y-auto kt-scrollable-y">
                                @foreach ($subjects as $s)
                                    <x-checkbox-card
                                        name="subject_ids[]"
                                        :value="$s->id"
                                        :title="$s->subject_name"
                                        :desc="$s->subject_code . ' | ' . ($s->section?->section_name ?? '—')"
                                        :checked="in_array($s->id, old('subject_ids', []))"
                                        data-subject-option />
                                @endforeach
                            </div>
                        </details>
                        @error('subject_ids')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="kt-form-label">Files</label>
                        <input type="file" name="files[]" class="kt-input" multiple required accept=".pdf,.ppt,.pptx,.ppsx,.doc,.docx,.rtf">
                        <div data-file-list data-empty="No files selected yet." class="flex flex-col gap-2 mt-1">
                            <span class="text-xs text-secondary-foreground">No files selected yet.</span>
                        </div>
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
