{{-- Outline-style KTUI toasts for flash + validation feedback. Replaces kt-alert boxes.
     Included once per layout; reads session('status'|'error') and $errors. --}}
@php
    $toasts = [];
    if (session('status')) { $toasts[] = ['message' => session('status'), 'variant' => 'success']; }
    if (session('error'))  { $toasts[] = ['message' => session('error'),  'variant' => 'destructive']; }
    foreach ($errors->all() as $e) { $toasts[] = ['message' => $e, 'variant' => 'destructive']; }
@endphp
@if ($toasts)
    <script nonce="{{ $cspNonce ?? '' }}">
        document.addEventListener('DOMContentLoaded', function () {
            if (!window.KTToast) return;
            @foreach ($toasts as $t)
            KTToast.show({ message: @json($t['message']), variant: @json($t['variant']), appearance: 'outline', dismiss: true });
            @endforeach
        });
    </script>
@endif
