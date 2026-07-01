{{-- Global submit-button spinner: on any form submit, show a right-side spinner on the
     clicked button and disable it. Skip forms with data-no-spinner. --}}
<script nonce="{{ $cspNonce ?? '' }}">
    (function () {
        function spin(form, e) {
            if (form.hasAttribute('data-no-spinner')) return;
            var btn = e.submitter || form.querySelector('button[type=submit],button:not([type])');
            if (!btn || btn.dataset.spinning) return;
            btn.dataset.spinning = '1';
            btn.setAttribute('aria-busy', 'true');
            btn.disabled = true;
            btn.insertAdjacentHTML('beforeend', ' <i class="ki-filled ki-loading animate-spin ms-2"></i>');
        }
        document.addEventListener('submit', function (e) { spin(e.target, e); }, true);
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
