<!DOCTYPE html>
{{-- Metronic (Tailwind/KTUI) auth shell — rebuilt from
     public/metronic-tailwind-html-demos/dist/html/demo1/authentication/classic/sign-in.html.
     Child views fill @yield('card') with a <form> using kt-card-content. --}}
<html class="h-full" data-kt-theme="true" data-kt-theme-mode="light" dir="ltr" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <title>@yield('title', config('app.name'))</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <link rel="shortcut icon" href="{{ asset('metronic-tailwind-html-demos/dist/assets/media/app/favicon.ico') }}" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" />
    <link href="{{ asset('metronic-tailwind-html-demos/dist/assets/vendors/keenicons/styles.bundle.css') }}" rel="stylesheet" />
    <link href="{{ asset('metronic-tailwind-html-demos/dist/assets/css/styles.css') }}" rel="stylesheet" />
</head>
<body class="antialiased flex h-full text-base text-foreground bg-background">
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

<div class="flex items-center justify-center grow">
    <div class="kt-card max-w-96 w-full">
        @yield('card')
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}" src="{{ asset('metronic-tailwind-html-demos/dist/assets/js/core.bundle.js') }}"></script>
<script nonce="{{ $cspNonce ?? '' }}" src="{{ asset('metronic-tailwind-html-demos/dist/assets/vendors/ktui/ktui.min.js') }}"></script>
@include('partials._toasts')
@include('partials._form-submit-spinner')
</body>
</html>
