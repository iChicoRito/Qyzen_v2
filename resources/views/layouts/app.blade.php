<!doctype html>
{{-- Tabler vertical layout — structure copied from template/layout-vertical.html, assets via asset('tabler/...') --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>@yield('title', config('app.name'))</title>
    <!-- BEGIN GLOBAL MANDATORY STYLES -->
    <link href="{{ asset('tabler/css/tabler.min.css') }}" rel="stylesheet" />
    <!-- BEGIN PLUGINS STYLES -->
    <link href="{{ asset('tabler/css/tabler-flags.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('tabler/css/tabler-vendors.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('tabler/css/tabler-themes.min.css') }}" rel="stylesheet" />
    @stack('styles')
    <style>
      @import url("https://rsms.me/inter/inter.css");
    </style>
  </head>
  <body>
    <!-- BEGIN GLOBAL THEME SCRIPT -->
    <script src="{{ asset('tabler/js/tabler-theme.min.js') }}"></script>
    <div class="page">
      <!-- BEGIN SIDEBAR -->
      <aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
        <div class="container-fluid">
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                  data-bs-target="#sidebar-menu" aria-controls="sidebar-menu"
                  aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
          </button>
          <div class="navbar-brand navbar-brand-autodark">
            <a href="{{ url('/') }}">{{ config('app.name') }}</a>
          </div>
          <div class="collapse navbar-collapse" id="sidebar-menu">
            <ul class="navbar-nav pt-lg-3">
              @foreach ($navItems ?? [] as $item)
                <li class="nav-item {{ ($item['active'] ?? false) ? 'active' : '' }}">
                  <a class="nav-link" href="{{ $item['url'] }}">
                    <span class="nav-link-title">{{ $item['label'] }}</span>
                  </a>
                </li>
              @endforeach
            </ul>
          </div>
        </div>
      </aside>
      <!-- END SIDEBAR -->

      <div class="page-wrapper">
        <!-- BEGIN PAGE HEADER -->
        <div class="page-header d-print-none" aria-label="Page header">
          <div class="container-xl">
            <div class="row g-2 align-items-center">
              <div class="col">
                <h2 class="page-title">@yield('heading', '')</h2>
              </div>
              <div class="col-auto ms-auto d-print-none">
                <span class="text-secondary">
                  {{ $role ?? 'guest' }} · {{ auth()->user()->name ?? 'Guest' }}
                </span>
              </div>
            </div>
          </div>
        </div>
        <!-- END PAGE HEADER -->

        <!-- BEGIN PAGE BODY -->
        <div class="page-body">
          <div class="container-xl">
            @yield('content')
          </div>
        </div>
        <!-- END PAGE BODY -->

        <footer class="footer footer-transparent d-print-none">
          <div class="container-xl">
            <div class="text-secondary text-center">{{ config('app.name') }}</div>
          </div>
        </footer>
      </div>
    </div>

    <!-- BEGIN GLOBAL SCRIPTS -->
    <script src="{{ asset('tabler/js/tabler.min.js') }}" defer></script>
    @stack('scripts')
  </body>
</html>
