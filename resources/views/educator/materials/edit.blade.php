@extends(request()->boolean('modal') ? 'layouts.fragment' : 'educator.layout')
@section('title', 'Edit Material')
@section('heading', 'Edit Material')
@section('content')
    @include('admin._status')
    <div class="kt-card"><div class="kt-card-content p-5">
        <form method="POST" action="{{ route('educator.materials.update', $material) }}">@csrf @method('PUT')
            <div class="grid grid-cols-3 gap-5">
                <div class="flex flex-col gap-1 col-span-2">
                    <label class="kt-form-label">File Name</label>
                    <input name="file_name" class="kt-input" value="{{ old('file_name', $material->file_name) }}">
                    @error('file_name')<span class="text-xs text-destructive mt-1">{{ $message }}</span>@enderror
                </div>
                <div class="flex flex-col gap-1">
                    <label class="kt-form-label">Status</label>
                    <select name="is_active" class="kt-select">
                        <option value="1" @selected(old('is_active', $material->is_active)==1)>Active</option>
                        <option value="0" @selected(old('is_active', $material->is_active)==0)>Inactive</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-2 mt-5"><button class="kt-btn kt-btn-primary">Save</button>
                <a href="{{ route('educator.materials.index') }}" class="kt-btn kt-btn-outline" data-modal-cancel>Cancel</a></div>
        </form>
    </div></div>
@endsection
