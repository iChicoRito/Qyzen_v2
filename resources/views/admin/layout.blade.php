{{-- Admin shell: wraps the Metronic light-sidebar layout with the admin nav (icons per item). --}}
@php
    $navItems = [
        ['heading' => 'Admin'],
        ['label' => 'Dashboard',     'url' => route('admin.dashboard'),            'active' => request()->routeIs('admin.dashboard'),          'icon' => 'element-11'],
        ['label' => 'Calendar',      'url' => route('admin.calendar'),             'active' => request()->routeIs('admin.calendar'),           'icon' => 'calendar'],
        ['label' => 'Users',         'url' => route('admin.users.index'),          'active' => request()->routeIs('admin.users.*'),            'icon' => 'users'],
        ['heading' => 'Access Control'],
        ['label' => 'Roles',         'url' => route('admin.roles.index'),          'active' => request()->routeIs('admin.roles.*'),            'icon' => 'security-user'],
        ['label' => 'Permissions',   'url' => route('admin.permissions.index'),    'active' => request()->routeIs('admin.permissions.*'),      'icon' => 'key'],
        ['heading' => 'Academic Settings'],
        ['label' => 'Academic Year', 'url' => route('admin.academic-years.index'), 'active' => request()->routeIs('admin.academic-years.*'),   'icon' => 'calendar'],
        ['label' => 'Academic Term', 'url' => route('admin.academic-terms.index'), 'active' => request()->routeIs('admin.academic-terms.*'),   'icon' => 'calendar-tick'],
        ['label' => 'Settings',      'url' => route('admin.settings.index'),       'active' => request()->routeIs('admin.settings.*'),         'icon' => 'setting-2'],
    ];
@endphp

@extends('layouts.app', ['role' => 'admin', 'navItems' => $navItems])
