<!DOCTYPE html>
{{-- Metronic (Tailwind/KTUI) demo1 layout — sidebar + header copied VERBATIM from
     public/metronic-tailwind-html-demos/dist/html/demo1/index.html. The header mega-menu +
     topbar (search/notifications/chat/apps) are in layouts/partials/_demo1_*.blade.php exactly
     as shipped (demo links/content retained). Only the sidebar menu items are app-driven ($navItems).
     $navItems: [['label','url','active'(bool),'icon'(ki-filled name, optional)]] or ['heading'=>'...']. --}}
<html class="h-full" data-kt-theme="true" data-kt-theme-mode="light" dir="ltr" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <title>@yield('title', config('app.name'))</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <link rel="shortcut icon" href="{{ asset('metronic-tailwind-html-demos/dist/assets/media/app/favicon.ico') }}" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" />
    @stack('styles')
    <link href="{{ asset('metronic-tailwind-html-demos/dist/assets/vendors/apexcharts/apexcharts.css') }}" rel="stylesheet" />
    <link href="{{ asset('metronic-tailwind-html-demos/dist/assets/vendors/keenicons/styles.bundle.css') }}" rel="stylesheet" />
    <link href="{{ asset('metronic-tailwind-html-demos/dist/assets/css/styles.css') }}" rel="stylesheet" />
</head>
<body class="antialiased flex h-full text-base text-foreground bg-background demo1 kt-sidebar-fixed kt-header-fixed">
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

@php $u = auth()->user(); $initial = strtoupper(mb_substr($u->given_name ?? ($u->name ?? '?'), 0, 1)); @endphp

<!-- Main -->
<div class="flex grow">
    <!-- Sidebar (demo1 chrome; items = $navItems) -->
    <div class="kt-sidebar bg-background border-e border-e-border fixed top-0 bottom-0 z-20 hidden lg:flex flex-col items-stretch shrink-0 [--kt-drawer-enable:true] lg:[--kt-drawer-enable:false]"
         data-kt-drawer="true" data-kt-drawer-class="kt-drawer kt-drawer-start top-0 bottom-0" id="sidebar">
        <div class="kt-sidebar-header hidden lg:flex items-center relative justify-between px-3 lg:px-6 shrink-0" id="sidebar_header">
            <div class="kt-sidebar-logo min-w-0">
                <a class="dark:hidden" href="{{ url('/') }}">
                    <img class="default-logo min-h-[22px] max-w-none" src="{{ asset('metronic-tailwind-html-demos/dist/assets/media/app/default-logo.svg') }}"/>
                    <img class="small-logo min-h-[22px] max-w-none" src="{{ asset('metronic-tailwind-html-demos/dist/assets/media/app/mini-logo.svg') }}"/>
                </a>
                <a class="hidden dark:block" href="{{ url('/') }}">
                    <img class="default-logo min-h-[22px] max-w-none" src="{{ asset('metronic-tailwind-html-demos/dist/assets/media/app/default-logo-dark.svg') }}"/>
                    <img class="small-logo min-h-[22px] max-w-none" src="{{ asset('metronic-tailwind-html-demos/dist/assets/media/app/mini-logo.svg') }}"/>
                </a>
            </div>
            <button class="kt-btn kt-btn-outline kt-btn-icon size-[30px] absolute start-full top-2/4 z-40 -translate-x-2/4 -translate-y-2/4 rtl:translate-x-2/4"
                    data-kt-toggle="body" data-kt-toggle-class="kt-sidebar-collapse" id="sidebar_toggle">
                <i class="ki-filled ki-black-left-line kt-toggle-active:rotate-180 transition-all duration-300 rtl:translate rtl:rotate-180 rtl:kt-toggle-active:rotate-0"></i>
            </button>
        </div>
        <div class="kt-sidebar-content flex grow shrink-0 py-5 pe-2" id="sidebar_content">
            <div class="kt-scrollable-y-hover grow shrink-0 flex ps-2 lg:ps-5 pe-1 lg:pe-3"
                 data-kt-scrollable="true" data-kt-scrollable-dependencies="#sidebar_header"
                 data-kt-scrollable-height="auto" data-kt-scrollable-offset="0px"
                 data-kt-scrollable-wrappers="#sidebar_content" id="sidebar_scrollable">
                <!-- Sidebar Menu -->
                <div class="kt-menu flex flex-col grow gap-1" data-kt-menu="true" id="sidebar_menu">
                    @foreach ($navItems ?? [] as $item)
                        @if (($item['heading'] ?? false))
                            <div class="kt-menu-item pt-2.25 pb-px">
                                <span class="kt-menu-heading uppercase text-xs font-medium text-muted-foreground ps-[10px] pe-[10px]">{{ $item['heading'] }}</span>
                            </div>
                        @else
                            <div class="kt-menu-item {{ ($item['active'] ?? false) ? 'active' : '' }}">
                                <a class="kt-menu-link flex items-center grow border border-transparent gap-[10px] ps-[10px] pe-[10px] py-[6px] kt-menu-item-active:bg-accent/60 kt-menu-item-active:rounded-lg hover:bg-accent/60 hover:rounded-lg"
                                   href="{{ $item['url'] }}" tabindex="0">
                                    <span class="kt-menu-icon items-start text-muted-foreground kt-menu-item-active:text-primary w-[20px]">
                                        <i class="ki-filled ki-{{ $item['icon'] ?? 'abstract-8' }} text-lg"></i>
                                    </span>
                                    <span class="kt-menu-title text-sm font-medium text-foreground kt-menu-item-active:text-primary kt-menu-link-hover:!text-primary">{{ $item['label'] }}</span>
                                </a>
                            </div>
                        @endif
                    @endforeach
                </div>
                <!-- End of Sidebar Menu -->
            </div>
        </div>
    </div>
    <!-- End of Sidebar -->

    <!-- Wrapper -->
    <div class="kt-wrapper flex grow flex-col">
        <!-- Header (verbatim demo1: mega-menu + topbar) -->
        <header class="kt-header fixed top-0 z-10 start-0 end-0 flex items-stretch shrink-0 bg-background"
                data-kt-sticky="true" data-kt-sticky-class="border-b border-border" data-kt-sticky-name="header" id="header">
            <!-- Container -->
            <div class="kt-container-fixed flex justify-between items-stretch lg:gap-4" id="headerContainer">
                <!-- Mobile Logo -->
                <div class="flex gap-2.5 lg:hidden items-center -ms-1">
                    <a class="shrink-0" href="{{ url('/') }}">
                        <img class="max-h-[25px] w-full" src="{{ asset('metronic-tailwind-html-demos/dist/assets/media/app/mini-logo.svg') }}"/>
                    </a>
                    <div class="flex items-center">
                        <button class="kt-btn kt-btn-icon kt-btn-ghost" data-kt-drawer-toggle="#sidebar">
                            <i class="ki-filled ki-menu"></i>
                        </button>
                        <button class="kt-btn kt-btn-icon kt-btn-ghost" data-kt-drawer-toggle="#mega_menu_wrapper">
                            <i class="ki-filled ki-burger-menu-2"></i>
                        </button>
                    </div>
                </div>
                <!-- End of Mobile Logo -->

                @include('layouts.partials._demo1_megamenu')

                @include('layouts.partials._demo1_topbar_icons')
                    <!-- User -->
                    <div class="shrink-0" data-kt-dropdown="true" data-kt-dropdown-offset="10px, 10px"
                         data-kt-dropdown-offset-rtl="-20px, 10px" data-kt-dropdown-placement="bottom-end"
                         data-kt-dropdown-placement-rtl="bottom-start" data-kt-dropdown-trigger="click">
                        <div class="cursor-pointer shrink-0" data-kt-dropdown-toggle="true">
                            @if ($u && $u->profile_picture)
                                <img alt="user" class="size-9 rounded-full border-2 border-primary shrink-0" src="{{ asset('storage/'.$u->profile_picture) }}" />
                            @else
                                <span class="inline-flex items-center justify-center size-9 rounded-full bg-primary/10 text-primary font-semibold shrink-0">{{ $initial }}</span>
                            @endif
                        </div>
                        <div class="kt-dropdown-menu w-[250px]" data-kt-dropdown-menu="true">
                            <div class="flex items-center justify-between px-2.5 py-1.5 gap-1.5">
                                <div class="flex items-center gap-2">
                                    @if ($u && $u->profile_picture)
                                        <img alt="user" class="size-9 shrink-0 rounded-full border-2 border-primary" src="{{ asset('storage/'.$u->profile_picture) }}" />
                                    @else
                                        <span class="inline-flex items-center justify-center size-9 rounded-full bg-primary/10 text-primary font-semibold shrink-0">{{ $initial }}</span>
                                    @endif
                                    <div class="flex flex-col gap-1.5">
                                        <span class="text-sm text-foreground font-semibold leading-none">{{ $u->name ?? 'Guest' }}</span>
                                        <span class="text-xs text-secondary-foreground font-medium leading-none">{{ $u->email ?? '' }}</span>
                                    </div>
                                </div>
                                <span class="kt-badge kt-badge-sm kt-badge-primary kt-badge-outline text-capitalize">{{ $role ?? 'guest' }}</span>
                            </div>
                            <ul class="kt-dropdown-menu-sub">
                                <li><div class="kt-dropdown-menu-separator"></div></li>
                                <li>
                                    <a class="kt-dropdown-menu-link" href="{{ route('profile.edit') }}">
                                        <i class="ki-filled ki-profile-circle"></i>
                                        My Profile
                                    </a>
                                </li>
                                <li><div class="kt-dropdown-menu-separator"></div></li>
                            </ul>
                            <div class="px-2.5 pt-1.5 mb-2.5 flex flex-col gap-3.5">
                                <div class="flex items-center gap-2 justify-between">
                                    <span class="flex items-center gap-2">
                                        <i class="ki-filled ki-moon text-base text-muted-foreground"></i>
                                        <span class="font-medium text-2sm">Dark Mode</span>
                                    </span>
                                    <input class="kt-switch" data-kt-theme-switch-state="dark" data-kt-theme-switch-toggle="true" name="check" type="checkbox" value="1" />
                                </div>
                                <form method="POST" action="{{ route('logout') }}">@csrf
                                    <button type="submit" class="kt-btn kt-btn-outline justify-center w-full">Sign Out</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <!-- End of User -->
                </div>
                <!-- End of Topbar -->
            </div>
            <!-- End of Container -->
        </header>
        <!-- End of Header -->

        <!-- Content -->
        <main class="grow pt-5" id="content" role="content">
            <!-- Toolbar -->
            <div class="kt-container-fixed">
                <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
                    <div class="flex flex-col justify-center gap-2">
                        <h1 class="text-xl font-medium leading-none text-mono">@yield('heading', config('app.name'))</h1>
                        <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                            <a href="{{ url('/'.($role ?? '').'/dashboard') }}" class="hover:text-primary text-capitalize">{{ $role ?? 'home' }}</a>
                            <span class="text-muted-foreground">/</span>
                            <span>@yield('heading', 'Overview')</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2.5">
                        @yield('toolbar')
                    </div>
                </div>
            </div>
            <!-- End of Toolbar -->

            <div class="kt-container-fixed">
                @yield('content')
            </div>
        </main>
        <!-- End of Content -->

        <!-- Footer -->
        <footer class="kt-footer">
            <div class="kt-container-fixed">
                <div class="flex flex-col md:flex-row justify-center md:justify-between items-center gap-3 py-5">
                    <div class="flex order-2 md:order-1 gap-2 font-normal text-sm">
                        <span class="text-secondary-foreground">{{ date('Y') }}©</span>
                        <span class="text-mono">{{ config('app.name') }}</span>
                    </div>
                </div>
            </div>
        </footer>
        <!-- End of Footer -->
    </div>
    <!-- End of Wrapper -->
</div>
<!-- End of Main -->

@include('layouts.partials._demo1_search_modal')

<script nonce="{{ $cspNonce ?? '' }}" src="{{ asset('metronic-tailwind-html-demos/dist/assets/js/core.bundle.js') }}"></script>
<script nonce="{{ $cspNonce ?? '' }}" src="{{ asset('metronic-tailwind-html-demos/dist/assets/vendors/ktui/ktui.min.js') }}"></script>
<script nonce="{{ $cspNonce ?? '' }}" src="{{ asset('metronic-tailwind-html-demos/dist/assets/vendors/apexcharts/apexcharts.min.js') }}"></script>
<script nonce="{{ $cspNonce ?? '' }}" src="{{ asset('metronic-tailwind-html-demos/dist/assets/js/widgets/general.js') }}"></script>
<script nonce="{{ $cspNonce ?? '' }}" src="{{ asset('metronic-tailwind-html-demos/dist/assets/js/layouts/demo1.js') }}"></script>
@stack('scripts')
</body>
</html>
