{{-- Reusable modal form loader. A trigger with data-modal-url + data-modal-target="#id"
     (+ optional data-modal-title) fetches that URL's form fragment (?modal=1) and injects it into
     the target modal, then opens it. Delegated on document → survives datatable redraws.
     Injected <script> never runs (innerHTML), so all in-modal behavior (repeater) is delegated. --}}
<script nonce="{{ $cspNonce ?? '' }}">
    (function () {
        function reinit() {
            {{-- This bundle has no window.KTMenu; menus/3-dots are KTDropdown. --}}
            if (window.KTDropdown) KTDropdown.init();
            if (window.KTModal) KTModal.init();
        }
        document.addEventListener('click', function (e) {
            var trigger = e.target.closest('[data-modal-url]');
            if (!trigger) return;
            e.preventDefault();

            var sel = trigger.getAttribute('data-modal-target') || '#form_modal';
            var modal = document.querySelector(sel);
            if (!modal) return;
            var body = modal.querySelector('[data-modal-body]');
            var titleEl = modal.querySelector('[data-modal-title]');
            if (titleEl && trigger.dataset.modalTitle) titleEl.textContent = trigger.dataset.modalTitle;
            if (body) body.innerHTML = '<div class="p-10 text-center text-secondary-foreground">Loading…</div>';

            var url = trigger.dataset.modalUrl;
            url += (url.indexOf('?') === -1 ? '?' : '&') + 'modal=1';
            fetch(url, { headers: { 'X-Requested-With': 'fetch' }, credentials: 'same-origin' })
                .then(function (r) { return r.text(); })
                .then(function (html) {
                    if (body) body.innerHTML = html;
                    // Let the global submit gate own validation feedback for injected forms too.
                    var mf = body && body.querySelector('form'); if (mf) mf.noValidate = true;
                    // Cancel buttons/links inside the fragment should dismiss, not navigate.
                    modal.querySelectorAll('[data-modal-cancel]').forEach(function (c) {
                        c.setAttribute('data-kt-modal-dismiss', 'true');
                        c.removeAttribute('href');
                        if (c.tagName === 'A') c.setAttribute('type', 'button');
                    });
                    // Pin the form's action row as a sticky footer so it stays visible while the
                    // body scrolls (buttons stay INSIDE the form, so submit still works).
                    var submit = body && body.querySelector('button[type=submit], button:not([type])');
                    var actions = submit && (submit.closest('[data-modal-cancel]') ? null : submit.parentElement);
                    if (actions) {
                        actions.classList.add(
                            'sticky', 'bottom-0', 'bg-background', 'border-t', 'border-border',
                            'py-3', 'mt-4', 'justify-end'
                        );
                        actions.style.marginBottom = '0';
                    }
                    reinit();
                    if (window.KTModal) KTModal.getOrCreateInstance(modal).show();
                })
                .catch(function () {
                    if (body) body.innerHTML = '<div class="p-10 text-center text-destructive">Could not load the form.</div>';
                });
        });

        {{-- Quiz type ⇄ MC choices toggle (delegated; works inside the injected modal form). --}}
        document.addEventListener('change', function (e) {
            var sel = e.target.closest('[data-quiz-type]');
            if (!sel) return;
            var form = sel.closest('form') || document;
            var mc = form.querySelector('[data-mc-choices]');
            if (mc) mc.hidden = sel.value !== 'multiple_choice';
        });

        {{-- CSP-safe form repeater (bulk permissions). Delegated so it works on injected DOM. --}}
        function renumberRepeater(list) {
            list.querySelectorAll('[data-repeater-row]').forEach(function (row, i) {
                var label = row.querySelector('[data-repeater-index]');
                if (label) label.textContent = i + 1;
                row.querySelectorAll('input, select, textarea').forEach(function (el) {
                    if (el.name) el.name = el.name.replace(/\[\d+\]/, '[' + i + ']');
                });
            });
        }
        document.addEventListener('click', function (e) {
            var add = e.target.closest('[data-repeater-add]');
            if (add) {
                e.preventDefault();
                var list = document.querySelector(add.getAttribute('data-repeater-add'));
                if (!list) return;
                var rows = list.querySelectorAll('[data-repeater-row]');
                var clone = rows[rows.length - 1].cloneNode(true);
                clone.querySelectorAll('input').forEach(function (el) { el.value = ''; });
                clone.querySelectorAll('select').forEach(function (el) { el.selectedIndex = 0; });
                list.appendChild(clone);
                renumberRepeater(list);
                return;
            }
            var rm = e.target.closest('[data-repeater-remove]');
            if (rm) {
                e.preventDefault();
                var row = rm.closest('[data-repeater-row]');
                var container = row && row.parentElement;
                if (container && container.querySelectorAll('[data-repeater-row]').length > 1) {
                    row.remove();
                    renumberRepeater(container);
                }
            }
        });
    })();
</script>
