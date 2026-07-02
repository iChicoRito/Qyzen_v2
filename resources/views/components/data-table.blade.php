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
<div class="kt-card kt-card-grid min-w-full">
    <div class="kt-card-header flex-wrap items-center gap-2 py-5 justify-between">
        @if ($search)
            <div class="w-80 max-w-full shrink-0">
                <label class="kt-input">
                    <i class="ki-filled ki-magnifier"></i>
                    <input data-kt-datatable-search="#{{ $id }}" placeholder="{{ $searchPlaceholder }}" type="text" value="" />
                </label>
            </div>
        @else
            <span></span>
        @endif
        @isset($filters)<div class="flex flex-wrap gap-2.5 shrink-0">{{ $filters }}</div>@endisset
    </div>
    <div class="kt-card-content">
        <div class="grid" data-kt-datatable="true" data-kt-datatable-state-save="false" data-kt-datatable-page-size="{{ $pageSize }}" id="{{ $id }}">
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
        selects.forEach(function (sel) { sel.addEventListener('change', applyFilters); });

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
