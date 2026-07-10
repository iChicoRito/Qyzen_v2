<form method="POST" action="{{ route('educator.scores.destroy', $score) }}" class="flex flex-col gap-4">
    @csrf
    @method('DELETE')
    <p class="text-sm text-secondary-foreground">Deleting this score removes the recorded attempt and cannot be undone.</p>
    <div class="flex flex-col gap-1">
        <label class="kt-form-label">Account password</label>
        <input type="password" name="password" class="kt-input" autocomplete="current-password" required autofocus>
        @error('password')<span class="text-xs text-destructive">{{ $message }}</span>@enderror
    </div>
    <div class="flex justify-end gap-2">
        <button type="button" class="kt-btn kt-btn-outline" data-kt-modal-dismiss="true">Cancel</button>
        <button type="submit" class="kt-btn kt-btn-outline kt-btn-destructive">Delete Score</button>
    </div>
</form>
