{{-- Reusable server-backed table card. Rows are rendered by Blade; search/filter/page-size
     are GET params fetched via XHR so only the card swaps — no full page reload. --}}
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
<style nonce="{{ $cspNonce ?? '' }}">
    #{{ $id }} th[data-sort] .kt-table-col { cursor: pointer; }
    #{{ $id }}_form { transition: opacity 0.15s; }
</style>

@php
    $currentPerPage = (int) request('per_page', $pageSize);
    $total = $paginator ? $paginator->total() : null;
@endphp

<div id="{{ $id }}_form" data-table-url="{{ url()->current() }}" class="kt-card kt-card-grid min-w-full">
    @if (request()->filled('sort'))
        <input type="hidden" data-table-control name="sort" value="{{ request('sort') }}">
        <input type="hidden" data-table-control name="direction" value="{{ request('direction', 'asc') }}">
    @endif
    <div id="{{ $id }}_header" class="kt-card-header flex-wrap items-center gap-2 py-5 {{ $search ? 'justify-between' : '' }}">
        @if ($search)
            <div class="w-full sm:w-80">
                <label class="kt-input">
                    <i class="ki-filled ki-magnifier"></i>
                    <input data-table-control name="search" placeholder="{{ $searchPlaceholder }}" type="text" value="{{ request('search') }}" autocomplete="off" />
                </label>
            </div>
        @endif
        @isset($filters)
        <div class="flex flex-wrap gap-2.5 max-w-full {{ $search ? 'shrink-0' : 'w-full' }}">
            {{ $filters }}
            <button type="button" data-table-reset class="kt-btn kt-btn-outline hidden">
                <i class="ki-filled ki-arrows-circle"></i> Reset
            </button>
        </div>
        @endisset
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
                    <select data-table-control class="kt-select w-20" name="per_page">
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
                            <a data-table-page class="kt-btn kt-btn-sm kt-btn-outline {{ $paginator->onFirstPage() ? 'disabled pointer-events-none opacity-50' : '' }}"
                               href="{{ $paginator->previousPageUrl() ?: '#' }}">Previous</a>
                            @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
                                <a data-table-page class="kt-btn kt-btn-sm {{ $page === $paginator->currentPage() ? 'kt-btn-primary' : 'kt-btn-outline' }}" href="{{ $url }}">{{ $page }}</a>
                            @endforeach
                            <a data-table-page class="kt-btn kt-btn-sm kt-btn-outline {{ $paginator->hasMorePages() ? '' : 'disabled pointer-events-none opacity-50' }}"
                               href="{{ $paginator->nextPageUrl() ?: '#' }}">Next</a>
                        </div>
                    @else
                        <span>{{ $slot->isEmpty() ? '0 rows' : '' }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
(function () {
    var id  = '{{ $id }}';
    var lsKey = id + '_per_page';
    var root;

    function buildUrl() {
        var p = new URLSearchParams();
        root.querySelectorAll('[data-table-control][name]').forEach(function (el) {
            if (el.disabled || !el.name) return;
            if ((el.type === 'checkbox' || el.type === 'radio') && !el.checked) return;
            if (el.value !== '') p.set(el.name, el.value);
        });
        return root.dataset.tableUrl + '?' + p;
    }

    function swap(url) {
        root.style.opacity = '0.5';
        root.style.pointerEvents = 'none';
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.ok ? r.text() : Promise.reject(); })
            .then(function (html) {
                var doc  = new DOMParser().parseFromString(html, 'text/html');
                var next = doc.getElementById(id + '_form');
                if (!next) { window.location.href = url; return; }
                root.replaceWith(next);
                history.pushState(null, '', url);
                initTable();
            })
            .catch(function () { window.location.href = url; });
    }

    function go() { swap(buildUrl()); }

    function setHidden(name, value) {
        var input = root.querySelector('input[type="hidden"][name="' + name + '"]');
        if (value === null) { if (input) input.remove(); return; }
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.setAttribute('data-table-control', '');
            root.prepend(input);
        }
        input.value = value;
    }

    function hasActiveFilters(params) {
        return [...params.keys()].some(function (k) {
            return k !== 'page' && k !== 'per_page' && params.get(k) !== '';
        });
    }

    function initTable() {
        root = document.getElementById(id + '_form');
        if (!root) return;
        var params = new URLSearchParams(window.location.search);

        // Restore per_page from localStorage (only when URL has no per_page param)
        var perPageSel = root.querySelector('select[name="per_page"]');
        if (perPageSel) {
            var saved = localStorage.getItem(lsKey);
            if (!params.has('per_page') && saved && perPageSel.querySelector('option[value="' + saved + '"]')) {
                perPageSel.value = saved;
                // ponytail: skip auto-swap on first load — server already paginated at default
            }
            perPageSel.addEventListener('change', function () {
                localStorage.setItem(lsKey, perPageSel.value);
                setHidden('page', null);
                go();
            });
        }

        // Restore filter selects from URL params and wire change events
        root.querySelectorAll('select[data-filter]').forEach(function (sel) {
            if (!sel.name) sel.name = sel.dataset.filter;
            sel.setAttribute('data-table-control', '');
            if (params.has(sel.name)) sel.value = params.get(sel.name);
            sel.addEventListener('change', function () {
                var changed = [sel.dataset.filter];
                var cleared = {};
                while (changed.length) {
                    var parent = changed.shift();
                    root.querySelectorAll('select[data-depends-on]').forEach(function (dependent) {
                        var parents = (dependent.dataset.dependsOn || '').split(',');
                        var key = dependent.dataset.filter;
                        if (!cleared[key] && parents.indexOf(parent) !== -1) {
                            dependent.value = '';
                            setHidden(dependent.name, null);
                            cleared[key] = true;
                            changed.push(key);
                        }
                    });
                }
                setHidden('page', null);
                go();
            });
        });

        // Reset button — show when any filter/sort/search is active
        var resetBtn = root.querySelector('[data-table-reset]');
        if (resetBtn) {
            resetBtn.classList.toggle('hidden', !hasActiveFilters(params));
            resetBtn.addEventListener('click', function () {
                var base = root.dataset.tableUrl;
                // Keep per_page if user set it
                var pp = localStorage.getItem(lsKey);
                var target = pp ? base + '?per_page=' + encodeURIComponent(pp) : base;
                history.pushState(null, '', target);
                swap(target);
            });
        }

        // Column sort
        var activeSort = params.get('sort');
        var activeDir  = params.get('direction') || 'asc';
        root.querySelectorAll('th[data-sort]').forEach(function (header) {
            header.setAttribute('aria-sort', header.dataset.sort === activeSort ? activeDir : 'none');
            var key = header.dataset.sort;
            var control = header.querySelector('.kt-table-col') || header;
            control.setAttribute('role', 'button');
            control.setAttribute('tabindex', '0');
            var doSort = function () {
                var curDir = params.get('direction') === 'desc' ? 'desc' : 'asc';
                var dir = params.get('sort') === key && curDir === 'asc' ? 'desc' : 'asc';
                setHidden('sort', key);
                setHidden('direction', dir);
                setHidden('page', null);
                go();
            };
            control.addEventListener('click', doSort);
            control.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); doSort(); }
            });
        });

        // Debounced search
        var searchInput = root.querySelector('input[name="search"]');
        if (searchInput) {
            var timer = null;
            searchInput.addEventListener('input', function () {
                clearTimeout(timer);
                timer = setTimeout(function () { setHidden('page', null); go(); }, 500);
            });
        }

        // Intercept pagination links only — NOT every <a> in the card, which would also
        // catch row-action links (e.g. "Question Pool") and AJAX-swap them instead of navigating.
        root.querySelectorAll('[data-table-page]').forEach(function (a) {
            var href = a.getAttribute('href');
            if (!href || href === '#') return;
            a.addEventListener('click', function (e) {
                if (e.metaKey || e.ctrlKey || e.shiftKey) return;
                e.preventDefault();
                swap(href);
            });
        });
    }

    initTable();
})();
</script>
@endpush
