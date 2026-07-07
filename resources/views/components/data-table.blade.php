{{-- Reusable server-backed table card. Rows are rendered by Blade; search/filter/page-size
     are GET params so the browser never receives the full dataset. --}}
@props([
    'id',
    'pageSize' => 10,
    'search' => true,
    'searchPlaceholder' => 'Search',
    'paginator' => null,
])
@unless ($search)
    <style nonce="{{ $cspNonce ?? '' }}">
        #{{ $id }}_header { justify-content: flex-start; }
    </style>
@endunless

@php
    $query = request()->query();
    $currentPerPage = (int) request('per_page', $pageSize);
    $total = $paginator ? $paginator->total() : null;
@endphp

<form id="{{ $id }}_form" method="GET" action="{{ url()->current() }}" class="kt-card kt-card-grid min-w-full">
    <div id="{{ $id }}_header" class="kt-card-header flex-wrap items-center gap-2 py-5 {{ $search ? 'justify-between' : '' }}">
        @if ($search)
            <div class="w-full sm:w-80">
                <label class="kt-input">
                    <i class="ki-filled ki-magnifier"></i>
                    <input name="search" placeholder="{{ $searchPlaceholder }}" type="text" value="{{ request('search') }}" autocomplete="off" />
                </label>
            </div>
        @endif
        @isset($filters)<div class="flex flex-wrap gap-2.5 max-w-full {{ $search ? 'shrink-0' : 'w-full' }}">{{ $filters }}</div>@endisset
    </div>
    <div class="kt-card-content">
        <div class="grid grid-cols-1" id="{{ $id }}">
            <div class="kt-scrollable-x-auto">
                <table class="kt-table table-auto kt-table-border">
                    {{ $head }}
                    <tbody>
                        {{ $slot }}
                    </tbody>
                </table>
            </div>
            <div class="kt-card-footer justify-center md:justify-between flex-col md:flex-row gap-5 text-secondary-foreground text-sm font-medium">
                <div class="flex items-center gap-2 order-2 md:order-1">
                    Show
                    <select class="kt-select w-20" name="per_page" data-table-submit>
                        @foreach ([10, 25, 50] as $size)
                            <option value="{{ $size }}" @selected($currentPerPage === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                    per page
                </div>
                <div class="flex flex-wrap items-center justify-center gap-3 order-1 md:order-2">
                    @if ($paginator)
                        <span>
                            @if ($total)
                                {{ $paginator->firstItem() }}-{{ $paginator->lastItem() }} of {{ $total }}
                            @else
                                0 of 0
                            @endif
                        </span>
                        <div class="flex items-center gap-1">
                            <a class="kt-btn kt-btn-sm kt-btn-outline {{ $paginator->onFirstPage() ? 'disabled pointer-events-none opacity-50' : '' }}"
                               href="{{ $paginator->previousPageUrl() ?: '#' }}">Previous</a>
                            @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
                                <a class="kt-btn kt-btn-sm {{ $page === $paginator->currentPage() ? 'kt-btn-primary' : 'kt-btn-outline' }}" href="{{ $url }}">{{ $page }}</a>
                            @endforeach
                            <a class="kt-btn kt-btn-sm kt-btn-outline {{ $paginator->hasMorePages() ? '' : 'disabled pointer-events-none opacity-50' }}"
                               href="{{ $paginator->nextPageUrl() ?: '#' }}">Next</a>
                        </div>
                    @else
                        <span>{{ $slot->isEmpty() ? '0 rows' : '' }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    (function () {
        var form = document.getElementById('{{ $id }}_form');
        if (!form) return;
        var params = new URLSearchParams(window.location.search);

        form.querySelectorAll('select[data-filter]').forEach(function (select) {
            if (!select.name) select.name = select.dataset.filter;
            if (params.has(select.name)) select.value = params.get(select.name);
            select.addEventListener('change', function () {
                form.querySelector('input[name="page"]')?.remove();
                form.submit();
            });
        });

        form.querySelectorAll('[data-table-submit], select[name="per_page"]').forEach(function (select) {
            select.addEventListener('change', function () {
                form.querySelector('input[name="page"]')?.remove();
                form.submit();
            });
        });

        var search = form.querySelector('input[name="search"]');
        if (search) {
            var timer = null;
            search.addEventListener('input', function () {
                clearTimeout(timer);
                timer = setTimeout(function () { form.submit(); }, 500);
            });
        }
    })();
</script>
@endpush
