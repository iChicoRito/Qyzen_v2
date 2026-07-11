<aside class="kt-card h-full lg:col-span-1 lg:sticky lg:top-24 self-start" id="announcement_timeline">
    <div class="kt-card-header">
        <h3 class="kt-card-title">Announcements timeline</h3>
    </div>
    <div class="kt-card-content pb-7">
        <div class="flex flex-col gap-5">
            @forelse ($announcements as $announcement)
                <div class="border-s-2 border-primary">
                    <div class="flex flex-wrap gap-2 items-center ps-3 mb-0.5">
                        <time class="text-xs text-secondary-foreground">{{ $announcement->created_at?->format('M j, Y') }}</time>
                        <span class="rounded-full size-1.5 bg-input"></span>
                        <span class="text-xs text-secondary-foreground">{{ $announcement->is_global ? 'All students' : ($announcement->subject?->subject_code ?? 'Subject') }}</span>
                    </div>
                    <h4 class="text-sm font-semibold text-mono leading-5.5 ps-3">{{ $announcement->title }}</h4>
                    <p class="text-sm text-foreground leading-5.5 ps-3 mt-1">{{ Str::limit(strip_tags($announcement->description ?: $announcement->body), 110) }}</p>
                </div>
            @empty
                <p class="text-sm text-secondary-foreground">No announcement activity yet.</p>
            @endforelse
        </div>
    </div>
</aside>
