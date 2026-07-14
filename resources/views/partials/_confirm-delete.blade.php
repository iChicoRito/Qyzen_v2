{{-- SweetAlert2 confirm for destructive actions. Delegated on document so it survives
     KTDataTable redraws. Any element with data-confirm="message" is intercepted; on confirm
     it submits its closest <form> (or data-confirm-form="#selector"). Replaces native confirm(). --}}
<script nonce="{{ $cspNonce ?? '' }}">
    document.addEventListener('click', function (e) {
        var el = e.target.closest('[data-confirm]');
        if (!el) return;
        e.preventDefault();
        var form = el.dataset.confirmForm
            ? document.querySelector(el.dataset.confirmForm)
            : el.closest('form') || (el.closest('.kt-menu-item') && el.closest('.kt-menu-item').querySelector('form'));
        if (!form) return;
        function submitForm() {
            if (form.requestSubmit) { form.requestSubmit(); return; }
            form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
        }
        if (!window.Swal) { submitForm(); return; } // fail-open if SweetAlert missing
        Swal.fire({
            title: el.dataset.confirmTitle || 'Are you sure?',
            text: el.dataset.confirm,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: el.dataset.confirmButton || 'Yes, delete it',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#dc2626',
            reverseButtons: true,
        }).then(function (r) { if (r.isConfirmed) submitForm(); });
    });
</script>
