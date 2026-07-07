<!DOCTYPE html>
{{-- Focused, distraction-free shell for the take-quiz screen (task 18, Phase 1): no sidebar,
     header, or footer — the whole width belongs to the questions. Same asset bundles as
     layouts/app.blade.php + SweetAlert2 for the warning / submit / time's-up dialogs. --}}
<html class="h-full" data-kt-theme="true" data-kt-theme-mode="light" dir="ltr" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <title>@yield('title', config('app.name'))</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <link rel="shortcut icon" href="{{ asset('assets/img/favicon.ico') }}" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" />
    @stack('styles')
    <link href="{{ asset('metronic-tailwind-html-demos/dist/assets/vendors/keenicons/styles.bundle.css') }}" rel="stylesheet" />
    <link href="{{ asset('metronic-tailwind-html-demos/dist/assets/css/styles.css') }}" rel="stylesheet" />
</head>
<body class="antialiased flex flex-col min-h-full text-base text-foreground bg-background">
<script nonce="{{ $cspNonce ?? '' }}">
    const defaultThemeMode = 'light';
    let themeMode;
    if (document.documentElement) {
        if (localStorage.getItem('kt-theme')) { themeMode = localStorage.getItem('kt-theme'); }
        else if (document.documentElement.hasAttribute('data-kt-theme-mode')) { themeMode = document.documentElement.getAttribute('data-kt-theme-mode'); }
        else { themeMode = defaultThemeMode; }
        if (themeMode === 'system') { themeMode = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'; }
        document.documentElement.classList.add(themeMode);
    }
</script>

<main class="grow" id="content">
    <div class="w-full px-4 lg:px-8 py-6">
        @yield('content')
    </div>
</main>

<script nonce="{{ $cspNonce ?? '' }}" src="{{ asset('metronic-tailwind-html-demos/dist/assets/js/core.bundle.js') }}"></script>
<script nonce="{{ $cspNonce ?? '' }}" src="{{ asset('metronic-tailwind-html-demos/dist/assets/vendors/ktui/ktui.min.js') }}"></script>
<script nonce="{{ $cspNonce ?? '' }}" src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
@stack('scripts')
</body>
</html>
