<!doctype html>
{{-- Tabler centered auth shell — from template/sign-in.html (page page-center) --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <title>@yield('title', config('app.name'))</title>
    <link href="{{ asset('tabler/css/tabler.min.css') }}" rel="stylesheet" />
    <style>@import url("https://rsms.me/inter/inter.css");</style>
  </head>
  <body class="d-flex flex-column">
    <script src="{{ asset('tabler/js/tabler-theme.min.js') }}"></script>
    <div class="page page-center">
      <div class="container container-tight py-4">
        <div class="text-center mb-4">
          <span class="navbar-brand navbar-brand-autodark h1">{{ config('app.name') }}</span>
        </div>
        @yield('card')
      </div>
    </div>
    <script src="{{ asset('tabler/js/tabler.min.js') }}" defer></script>
  </body>
</html>
