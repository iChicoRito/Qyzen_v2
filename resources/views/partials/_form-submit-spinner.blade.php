{{-- Global submit gate: on any form submit, validate first (native Constraint Validation API).
     If invalid, show app-styled red text below each offending field and DON'T spin/submit.
     Only when all constraints pass do we spin the clicked button. Skip spinner on data-no-spinner.
     noValidate is set on every form so the browser's native bubbles never appear — this JS is the
     single, consistently-styled source of feedback (JS-off degrades to server-side @error). --}}
<script nonce="{{ $cspNonce ?? '' }}">
    (function () {
        // Suppress native validation bubbles so our JS owns the feedback UI.
        document.querySelectorAll('form').forEach(function (f) { f.noValidate = true; });

        function clearErrors(form) {
            form.querySelectorAll('[data-client-error]').forEach(function (n) { n.remove(); });
        }
        function showErrors(form) {
            var first = null;
            Array.prototype.forEach.call(form.elements, function (el) {
                if (!el.willValidate || el.checkValidity()) return;
                var span = document.createElement('span');
                span.className = 'text-xs text-destructive mt-1';
                span.setAttribute('data-client-error', '');
                span.textContent = el.validationMessage;
                (el.closest('.flex.flex-col') || el.parentElement).appendChild(span);
                if (!first) first = el;
            });
            if (first) first.focus();
        }

        function spin(form, e) {
            if (form.hasAttribute('data-no-spinner')) return;
            var btn = e.submitter || form.querySelector('button[type=submit],button:not([type])');
            if (!btn || btn.dataset.spinning) return;
            btn.dataset.spinning = '1';
            btn.setAttribute('aria-busy', 'true');
            btn.disabled = true;
            btn.insertAdjacentHTML('beforeend', ' <i class="ki-filled ki-loading animate-spin ms-2"></i>');
        }
        document.addEventListener('submit', function (e) {
            var form = e.target;
            clearErrors(form);
            if (form.checkValidity && !form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation(); // block submission + any other submit handlers
                showErrors(form);
                return; // invalid → no spinner
            }
            spin(form, e);
        }, true);
        // bfcache back-button: un-stick any spinner left disabled.
        window.addEventListener('pageshow', function () {
            document.querySelectorAll('button[data-spinning]').forEach(function (btn) {
                btn.disabled = false;
                btn.removeAttribute('aria-busy');
                delete btn.dataset.spinning;
                var i = btn.querySelector('i.ki-loading'); if (i) i.remove();
            });
        });
    })();
</script>
