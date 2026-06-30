{{-- Admin shell: wraps the Metronic app layout with the admin sidebar nav.
     Active item resolved from the current route name. --}}
@php
    $navItems = [
        ['label' => 'Dashboard',    'url' => route('admin.dashboard'),        'active' => request()->routeIs('admin.dashboard')],
        ['label' => 'Users',        'url' => route('admin.users.index'),      'active' => request()->routeIs('admin.users.*')],
        ['label' => 'Roles',        'url' => route('admin.roles.index'),      'active' => request()->routeIs('admin.roles.*')],
        ['label' => 'Permissions',  'url' => route('admin.permissions.index'), 'active' => request()->routeIs('admin.permissions.*')],
        ['label' => 'Academic Year', 'url' => route('admin.academic-years.index'), 'active' => request()->routeIs('admin.academic-years.*')],
        ['label' => 'Academic Term', 'url' => route('admin.academic-terms.index'), 'active' => request()->routeIs('admin.academic-terms.*')],
    ];
@endphp

@extends('layouts.app', ['role' => 'admin', 'navItems' => $navItems])
