<form method="POST" action="{{ $action }}" enctype="multipart/form-data" data-announcement-form>
    @csrf @if ($method !== 'POST') @method($method) @endif
    <div class="flex flex-col gap-5">
        <div class="flex flex-col gap-1.5"><label class="kt-form-label">Title</label><input class="kt-input" name="title" required maxlength="255" value="{{ old('title', $announcement?->title) }}">@error('title')<span class="text-xs text-destructive">{{ $message }}</span>@enderror</div>
        <div class="flex flex-col gap-1.5"><label class="kt-form-label">Description <span class="text-secondary-foreground">(Optional)</span></label><textarea class="kt-textarea" name="description" rows="2" maxlength="1000">{{ old('description', $announcement?->description) }}</textarea>@error('description')<span class="text-xs text-destructive">{{ $message }}</span>@enderror</div>
        <div class="flex flex-col gap-1.5">
            <label class="kt-form-label">Announcement Body</label>
            <div class="border border-border rounded-lg overflow-hidden">
                <div class="flex gap-1 p-2 border-b border-border"><button type="button" class="kt-btn kt-btn-sm kt-btn-ghost" data-editor-command="bold">B</button><button type="button" class="kt-btn kt-btn-sm kt-btn-ghost italic" data-editor-command="italic">I</button><button type="button" class="kt-btn kt-btn-sm kt-btn-ghost" data-editor-command="insertUnorderedList">• List</button><button type="button" class="kt-btn kt-btn-sm kt-btn-ghost" data-editor-command="insertOrderedList">1. List</button></div>
                <div class="p-3 min-h-32" contenteditable="true" data-editor>{{ old('body', $announcement?->body) }}</div>
            </div>
            <input type="hidden" name="body" data-editor-value value="{{ old('body', $announcement?->body) }}">
            @error('body')<span class="text-xs text-destructive">{{ $message }}</span>@enderror
        </div>
        <div class="flex flex-col gap-1.5"><label class="kt-form-label">Target</label><select class="kt-select" name="subject_id" data-subject-select><option value="">Select subject</option>@foreach ($subjects as $subject)<option value="{{ $subject->id }}" @selected((string) old('subject_id', $announcement?->subject_id) === (string) $subject->id)>{{ $subject->subject_code }} — {{ $subject->subject_name }} ({{ $subject->section?->section_name ?? '—' }})</option>@endforeach</select>@error('subject_id')<span class="text-xs text-destructive">{{ $message }}</span>@enderror</div>
        <input type="hidden" name="is_global" value="0"><x-checkbox-card variant="switch" name="is_global" value="1" title="Global announcement" desc="Show this announcement to every active student enrolled with you." :checked="(bool) old('is_global', $announcement?->is_global)" data-global-switch />
        <div class="flex flex-col gap-1.5"><label class="kt-form-label">Images <span class="text-secondary-foreground">(Optional, 10 MB maximum per file)</span></label><input class="kt-input" type="file" name="images[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple><span class="text-xs text-secondary-foreground">New images replace existing images when editing.</span>@error('images.*')<span class="text-xs text-destructive">{{ $message }}</span>@enderror</div>
        @if (($announcement?->images ?? []) !== [])<div class="flex flex-wrap gap-2">@foreach ($announcement->images as $image)<img class="size-20 object-cover rounded-lg" src="{{ route('student.announcements.image', [$announcement, $loop->index]) }}" alt="{{ $image['name'] ?? 'Announcement image' }}">@endforeach</div>@endif
    </div>
    <div class="flex gap-2 mt-5"><button class="kt-btn kt-btn-primary">{{ $method === 'POST' ? 'Create' : 'Save changes' }}</button><a href="{{ route('educator.announcements.index') }}" class="kt-btn kt-btn-outline" data-modal-cancel>Cancel</a></div>
</form>
<script nonce="{{ $cspNonce ?? '' }}">
document.querySelectorAll('[data-announcement-form]').forEach(function (form) {
    var editor = form.querySelector('[data-editor]'), value = form.querySelector('[data-editor-value]');
    form.querySelectorAll('[data-editor-command]').forEach(function (button) { button.addEventListener('click', function () { editor.focus(); document.execCommand(button.dataset.editorCommand, false); value.value = editor.innerHTML; }); });
    form.addEventListener('submit', function () { value.value = editor.innerHTML; });
    var global = form.querySelector('[data-global-switch]'), subject = form.querySelector('[data-subject-select]');
    if (global && subject) { var sync = function () { subject.disabled = global.checked; }; global.addEventListener('change', sync); sync(); }
});
</script>
