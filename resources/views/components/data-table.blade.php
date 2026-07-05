{{-- Reusable KTDataTable card, standardized from /admin/users. Slots:
       $head    — the <thead><tr>…</tr></thead> column headers
       default  — the <tbody> rows (@forelse … @empty)
       $filters — optional GET filter <select>s shown beside the search box
     Props: id, title, pageSize, search, searchPlaceholder. --}}
@props([
    'id',
    'pageSize' => 10,
    'search' => true,
    'searchPlaceholder' => 'Search',
])
@unless ($search)
    {{-- .kt-card-header's own component CSS hardcodes justify-content:space-between, which (with
         no search box to balance against) shoves the filters to the far right. Override by id —
         a utility class can't win here since .kt-card-header's rule comes later in the bundle. --}}
    <style nonce="{{ $cspNonce ?? '' }}">
        #{{ $id }}_header { justify-content: flex-start; }
    </style>
@endunless
<div class="kt-card kt-card-grid min-w-full">
    <div id="{{ $id }}_header" class="kt-card-header flex-wrap items-center gap-2 py-5 {{ $search ? 'justify-between' : '' }}">
        @if ($search)
            {{-- w-full on mobile (a fixed w-80=320px here is shrink-resistant and floors the
                 whole page ~35px too wide); sm:w-80 restores the 320px box on real screens. --}}
            <div class="w-full sm:w-80">
                <label class="kt-input">
                    <i class="ki-filled ki-magnifier"></i>
                    <input data-kt-datatable-search="#{{ $id }}" placeholder="{{ $searchPlaceholder }}" type="text" value="" />
                </label>
            </div>
        @endif
        {{-- max-w-full: cap the filters row at the card width so its selects wrap onto
             new lines on mobile instead of overflowing the page. --}}
        @isset($filters)<div class="flex flex-wrap gap-2.5 max-w-full {{ $search ? 'shrink-0' : 'w-full' }}">{{ $filters }}</div>@endisset
    </div>
    <div class="kt-card-content">
        {{-- grid-cols-1 = minmax(0,1fr): caps the track at the container width so the
             wide table scrolls INSIDE .kt-scrollable-x-auto instead of pushing the page. --}}
        <div class="grid grid-cols-1" data-kt-datatable="true" data-kt-datatable-state-save="false" data-kt-datatable-page-size="{{ $pageSize }}" id="{{ $id }}">
            <div class="kt-scrollable-x-auto">
                <table class="kt-table table-auto kt-table-border" data-kt-datatable-table="true">
                    {{ $head }}
                    <tbody>
                        {{ $slot }}
                    </tbody>
                </table>
            </div>
            <div class="kt-card-footer justify-center md:justify-between flex-col md:flex-row gap-5 text-secondary-foreground text-sm font-medium">
                <div class="flex items-center gap-2 order-2 md:order-1">
                    Show
                    <select class="kt-select w-16" data-kt-datatable-size="true" name="perpage"></select>
                    per page
                </div>
                <div class="flex items-center gap-4 order-1 md:order-2">
                    <span data-kt-datatable-info="true"></span>
                    <div class="kt-datatable-pagination" data-kt-datatable-pagination="true"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
    (function () {
        var wrap = document.querySelector('#{{ $id }}');
        if (!wrap) return;
        var card = wrap.closest('.kt-card');

        {{-- Client-side dropdown filters: each <select data-filter="key"> hides rows whose
             matching token doesn't equal the picked value. No page reload; filters combine (AND).
             KTDataTable REPLACES the whole <tbody> on every draw and rebuilds each <tr> from cell
             innerHTML — so we must (a) re-query tbody/rows live each time, never cache them, and
             (b) keep the match token INSIDE a cell as <span data-filter-value hidden>. --}}
        var selects = card ? card.querySelectorAll('select[data-filter]') : [];
        {{-- KTDataTable's own pagination only ever renders ONE page's worth of <tr> at a time —
             our filter can only toggle .hidden on rows already in the DOM, so a match sitting on
             another page is invisible no matter what. Fix: while any dropdown filter is active,
             force the table onto a single unpaginated "page" (so every row is in the DOM and our
             filter can actually see all of them); restore the original page size once every
             filter is cleared back to "All". --}}
        var originalPageSize = parseInt(wrap.getAttribute('data-kt-datatable-page-size'), 10) || 10;
        var UNPAGINATED_SIZE = 1000000;
        function hasActiveFilter() {
            return Array.prototype.some.call(selects, function (sel) { return !!sel.value; });
        }
        function syncPageSize() {
            if (typeof KTDataTable === 'undefined') return;
            var dt = KTDataTable.getInstance(wrap);
            if (!dt || typeof dt.setPageSize !== 'function' || typeof dt.getState !== 'function') return;
            var want = hasActiveFilter() ? UNPAGINATED_SIZE : originalPageSize;
            if (dt.getState().pageSize !== want) dt.setPageSize(want);
        }
        function rowMatches(row) {
            var ok = true;
            selects.forEach(function (sel) {
                var want = sel.value;
                if (!want) return;
                var token = row.querySelector('[data-filter-value="' + sel.dataset.filter + '"]');
                if (!token || token.getAttribute('data-filter-key') !== want) ok = false;
            });
            return ok;
        }
        function applyFilters() {
            var tbody = wrap.querySelector('tbody'); // live: KTDataTable swaps the tbody on each draw
            if (!tbody) return;
            tbody.querySelectorAll('tr').forEach(function (row) {
                if (row.querySelector('td[colspan]')) return; // skip the empty-state row
                row.classList.toggle('hidden', !rowMatches(row));
            });
        }
        selects.forEach(function (sel) {
            sel.addEventListener('change', function () {
                syncPageSize(); // triggers its own redraw + 'drew' -> applyFilters() when size changes
                applyFilters(); // also re-apply directly: switching between two active filters doesn't
            });               // change pageSize, so no redraw would otherwise happen
        });

        {{-- KTDataTable re-renders tbody on search/sort/paginate, dropping per-row dropdown
             instances and our hidden state. Re-init dropdowns + re-apply filters after each 'drew'. --}}
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof KTDataTable !== 'undefined') {
                var dt = KTDataTable.getInstance(wrap);
                if (dt && typeof dt.on === 'function') {
                    dt.on('drew', function () {
                        {{-- KTDataTable rebuilds <tbody> on each draw → the new rows' 3-dots
                             dropdowns are unregistered. Re-init them. NOTE: this bundle has no
                             window.KTMenu; the 3-dots is a KTDropdown. --}}
                        if (typeof KTDropdown !== 'undefined') KTDropdown.init();
                        applyFilters();
                    });
                }
            }
            applyFilters();
        });
    })();
</script>
@endpush
