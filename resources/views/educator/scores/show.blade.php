{{-- G7: attempt detail. correct_answer rendered here is server-side (educator view) — never served
     to a student. Read-only; educator may grant a retake. Fragment inside the shared modal under ?modal=1.
     Layout mirrors demo1 public-profile/teams.html team card. --}}
@php
    $isModal = request()->boolean('modal');
    $pct = $score->total_questions ? round($score->score / $score->total_questions * 100) : 0;
@endphp
@extends($isModal ? 'layouts.fragment' : 'educator.layout')
@section('title', 'Attempt Detail')
@section('heading', 'Attempt Detail')
@section('content')
    @include('admin._status')
    <div class="kt-card">
        <div class="kt-card-content grid gap-7 py-7.5">
            {{-- Centered identity --}}
            <div class="grid place-items-center gap-4">
                <div class="flex justify-center items-center size-14 rounded-full ring-1 ring-input bg-accent">
                    <i class="ki-filled ki-medal-star text-2xl text-muted-foreground"></i>
                </div>
                <div class="grid place-items-center">
                    <span class="text-base font-medium text-mono mb-px">{{ optional($score->student)->name }}</span>
                    <span class="text-sm text-secondary-foreground text-center">{{ optional($score->student)->user_id }} · {{ optional($score->assessment)->assessment_code }}</span>
                </div>
            </div>

            {{-- Summary rows --}}
            <div class="grid">
                <div class="flex items-center justify-between flex-wrap mb-2.5 gap-2">
                    <span class="text-xs text-secondary-foreground uppercase">Score</span>
                    <span class="text-sm text-mono font-semibold">{{ $score->score }}/{{ $score->total_questions }}</span>
                </div>
                <div class="border-t border-input border-dashed"></div>
                <div class="flex items-center justify-between flex-wrap my-2.5 gap-2">
                    <span class="text-xs text-secondary-foreground uppercase">Percentage</span>
                    <span class="text-sm text-mono font-semibold">{{ $pct }}%</span>
                </div>
                <div class="border-t border-input border-dashed"></div>
                <div class="flex items-center justify-between flex-wrap my-2.5 gap-2">
                    <span class="text-xs text-secondary-foreground uppercase">Result</span>
                    <span class="kt-badge kt-badge-outline kt-badge-{{ $score->is_passed ? 'success' : 'destructive' }}">{{ $score->is_passed ? 'Passed' : 'Failed' }}</span>
                </div>
                <div class="border-t border-input border-dashed"></div>
                <div class="flex items-center justify-between flex-wrap mt-2.5 gap-2">
                    <span class="text-xs text-secondary-foreground uppercase">Warnings</span>
                    <span class="kt-badge kt-badge-outline kt-badge-{{ $score->warning_attempts > 0 ? 'destructive' : 'secondary' }}">{{ $score->warning_attempts }}</span>
                </div>
            </div>

            {{-- Grant retake --}}
            <form method="POST" action="{{ route('educator.scores.grant-retake') }}" class="flex gap-2 items-end">@csrf
                <input type="hidden" name="assessment_id" value="{{ $score->assessment_id }}">
                <input type="hidden" name="student_id" value="{{ $score->student_id }}">
                <div class="flex flex-col gap-1">
                    <label class="kt-form-label">Grant retakes</label>
                    <input type="number" name="extra_retake_count" class="kt-input w-40" min="1" value="1">
                </div>
                <button class="kt-btn kt-btn-primary">Grant retake</button>
            </form>

            {{-- Per-question review --}}
            <div class="grid gap-3">
                <h4 class="text-sm font-semibold text-mono">Per-question review</h4>
                @php $answers = $score->student_answer ?? []; @endphp
                <div class="kt-scrollable-x-auto">
                    <table class="kt-table table-auto kt-table-border">
                        <thead>
                            <tr>
                                <th class="min-w-[260px]">Question</th>
                                <th class="min-w-[140px]">Student Answer</th>
                                <th class="min-w-[140px]">Correct Answer</th>
                                <th class="min-w-[100px]">Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reviewQuestions as $quiz)
                                @php
                                    $given = $answers[$quiz->id] ?? ($answers[(string) $quiz->id] ?? null);
                                    $correct = $quiz->correct_answer; // server-side only
                                    $isCorrect = $given !== null && (string) $given === (string) $correct;
                                @endphp
                                <tr>
                                    <td>{{ \Illuminate\Support\Str::limit($quiz->question, 60) }}</td>
                                    <td>{{ $given ?? '—' }}</td>
                                    <td>{{ $correct }}</td>
                                    <td>
                                        <span class="kt-badge rounded-full kt-badge-outline kt-badge-{{ $isCorrect ? 'success' : 'destructive' }} gap-1 items-center">
                                            <span class="kt-badge-dot size-1.5"></span>{{ $isCorrect ? 'Correct' : 'Wrong' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @unless ($isModal)
            {{-- In-modal, closing is via the modal header ✕ (the grant-retake form is auto-pinned as
                 the sticky action row by _modal-loader, so a second footer button would clash). --}}
            <div class="kt-card-footer justify-end">
                <a href="{{ route('educator.scores.index') }}" class="kt-btn kt-btn-outline">Back</a>
            </div>
        @endunless
    </div>
@endsection
