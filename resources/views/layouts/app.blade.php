<!DOCTYPE html>
{{-- Metronic layout — structure copied from public/metronic/dist/index.html, assets via asset('metronic/dist/assets/...') --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <title>@yield('title', config('app.name'))</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    @stack('styles')
    <link href="{{ asset('metronic/dist/assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('metronic/dist/assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
</head>
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true"
      data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true"
      data-kt-app-sidebar-hoverable="true" class="app-default">
    <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
        <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
            <!--begin::Header-->
            <div id="kt_app_header" class="app-header">
                <div class="app-container container-fluid d-flex align-items-stretch justify-content-between" id="kt_app_header_container">
                    <div class="d-flex align-items-center d-lg-none ms-n3" title="Show sidebar menu">
                        <div class="btn btn-icon btn-active-color-primary w-35px h-35px" id="kt_app_sidebar_mobile_toggle">
                            <i class="ki-duotone ki-abstract-14 fs-2"><span class="path1"></span><span class="path2"></span></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-stretch justify-content-between flex-lg-grow-1" id="kt_app_header_wrapper">
                        <div class="app-navbar flex-shrink-0 align-items-center">
                            <span class="text-gray-700 fw-semibold">
                                {{ $role ?? 'guest' }} · {{ auth()->user()->name ?? 'Guest' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <!--end::Header-->

            <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
                <!--begin::Sidebar-->
                <div id="kt_app_sidebar" class="app-sidebar flex-column"
                     data-kt-drawer="true" data-kt-drawer-name="app-sidebar"
                     data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true"
                     data-kt-drawer-width="225px" data-kt-drawer-direction="start"
                     data-kt-drawer-toggle="#kt_app_sidebar_mobile_toggle">
                    <div class="app-sidebar-logo px-6" id="kt_app_sidebar_logo">
                        <a href="{{ url('/') }}" class="text-white fs-2 fw-bold">{{ config('app.name') }}</a>
                    </div>
                    <div class="app-sidebar-menu overflow-hidden flex-column-fluid">
                        <div id="kt_app_sidebar_menu_wrapper" class="app-sidebar-wrapper">
                            <div class="menu menu-column menu-rounded menu-sub-indention fw-semibold fs-6"
                                 id="kt_app_sidebar_menu" data-kt-menu="true">
                                @foreach ($navItems ?? [] as $item)
                                    <div class="menu-item">
                                        <a class="menu-link {{ ($item['active'] ?? false) ? 'active' : '' }}" href="{{ $item['url'] }}">
                                            <span class="menu-title">{{ $item['label'] }}</span>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <!--end::Sidebar-->

                <!--begin::Main-->
                <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
                    <div class="d-flex flex-column flex-column-fluid">
                        @hasSection('heading')
                            <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                                <div class="app-container container-fluid">
                                    <h1 class="page-heading text-dark fw-bold fs-3">@yield('heading')</h1>
                                </div>
                            </div>
                        @endif
                        <div id="kt_app_content" class="app-content flex-column-fluid">
                            <div id="kt_app_content_container" class="app-container container-fluid">
                                @yield('content')
                            </div>
                        </div>
                    </div>

                    <div id="kt_app_footer" class="app-footer">
                        <div class="app-container container-fluid d-flex align-items-center justify-content-center">
                            <span class="text-muted fw-semibold py-3">{{ config('app.name') }}</span>
                        </div>
                    </div>
                </div>
                <!--end::Main-->
            </div>
        </div>
    </div>

    <script nonce="{{ $cspNonce ?? '' }}">var hostUrl = "{{ asset('metronic/dist/assets/') }}/";</script>
    <script src="{{ asset('metronic/dist/assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('metronic/dist/assets/js/scripts.bundle.js') }}"></script>
    @stack('scripts')
</body>
</html>
