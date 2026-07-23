@extends('educator.layout')
@section('title', 'Deleted Scores')
@section('heading', 'Deleted Scores')
@section('content')
    @include('admin._status')

    <x-data-table id="deleted_scores_table" :search="false" :paginator="$scores">
        <x-slot:head>
            <thead>
                <tr>
                    <th class="min-w-[220px]">Student</th>
                    <th class="min-w-[140px]">Assessment</th>
                    <th class="min-w-[160px]">Subject</th>
                    <th class="min-w-[110px]">Section</th>
                    <th class="min-w-[110px]">Score</th>
                    <th class="min-w-[160px]">Deleted</th>
                    <th class="w-[110px]"></th>
                </tr>
            </thead>
        </x-slot:head>
        @forelse ($scores as $score)
            @php
                $student = $score->student;
                $initial = strtoupper(mb_substr($student?->surname ?: ($student?->given_name ?: '?'), 0, 1));
            @endphp
            <tr data-score-row="{{ $score->uuid }}">
                <td>
                    <div class="flex items-center gap-2.5">
                        <span class="inline-flex items-center justify-center rounded-full size-9 shrink-0 bg-primary/10 text-primary text-sm font-semibold">{{ $initial }}</span>
                        <span class="text-sm">
                            <span class="text-mono font-semibold">{{ $student?->surname ?? '—' }}</span>
                            <span class="text-secondary-foreground">{{ $student?->given_name }}</span>
                        </span>
                    </div>
                </td>
                <td>{{ $score->assessment?->assessment_code ?? '—' }}</td>
                <td>{{ $score->subject?->subject_code ?? '—' }}</td>
                <td>{{ $score->section?->section_name ?? '—' }}</td>
                <td>{{ $score->score ?? 0 }}/{{ $score->total_questions }}</td>
                <td class="text-secondary-foreground">{{ optional($score->deleted_at)->format('Y-m-d H:i') }}</td>
                <td>
                    <form method="POST" action="{{ route('educator.scores.restore', $score) }}" data-native-submit data-no-spinner data-score-restore>
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="kt-btn kt-btn-sm kt-btn-outline">
                            Restore
                        </button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-secondary-foreground py-5">No deleted scores.</td></tr>
        @endforelse
    </x-data-table>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}" data-ajax-rerun>
(function () {
    if (window.qyzenScoreRestoreBound) return;
    window.qyzenScoreRestoreBound = true;

    var token = @json(csrf_token());

    function parseResponse(response) {
        return response.text().then(function (text) {
            var data = {};
            try { data = text ? JSON.parse(text) : {}; } catch (_) { data = {}; }
            if (!response.ok) throw data;
            return data;
        });
    }

    function restoreScore(form) {
        var button = form.querySelector('button[type="submit"]');
        if (button.disabled) return;
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');

        fetch(form.action, {
            method: 'PATCH',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
        }).then(parseResponse).then(function (data) {
            var row = form.closest('[data-score-row]');
            if (row) row.remove();
            if (window.KTToast) {
                KTToast.show({
                    message: data.message || 'Score restored.',
                    variant: 'success',
                    appearance: 'outline',
                    dismiss: true,
                });
            }
        }).catch(function (data) {
            if (window.KTToast) {
                KTToast.show({
                    message: (data && data.message) || 'Could not restore the score.',
                    variant: 'destructive',
                    appearance: 'outline',
                    dismiss: true,
                });
            }
        }).finally(function () {
            button.disabled = false;
            button.removeAttribute('aria-busy');
        });
    }

    document.addEventListener('submit', function (event) {
        var form = event.target.closest('[data-score-restore]');
        if (!form) return;
        event.preventDefault();
        restoreScore(form);
    });
})();
</script>
@endpush
