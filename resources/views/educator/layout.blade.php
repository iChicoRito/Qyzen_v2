{{-- Educator shell: wraps the Metronic app layout with the educator sidebar nav.
     Active item resolved from the current route name. --}}
@php
    $navItems = [
        ['label' => 'Dashboard',   'url' => route('educator.dashboard'),       'active' => request()->routeIs('educator.dashboard')],
        ['label' => 'Sections',    'url' => route('educator.sections.index'),  'active' => request()->routeIs('educator.sections.*')],
        ['label' => 'Subjects',    'url' => route('educator.subjects.index'),  'active' => request()->routeIs('educator.subjects.*')],
        ['label' => 'Enrollment',  'url' => route('educator.enrollment.index'), 'active' => request()->routeIs('educator.enrollment.*')],
        ['label' => 'Assessments', 'url' => route('educator.assessments.index'), 'active' => request()->routeIs('educator.assessments.*')],
        ['label' => 'Quizzes',     'url' => route('educator.quizzes.index'),   'active' => request()->routeIs('educator.quizzes.*')],
        ['label' => 'Scores',      'url' => route('educator.scores.index'),    'active' => request()->routeIs('educator.scores.*')],
        ['label' => 'Materials',   'url' => route('educator.materials.index'), 'active' => request()->routeIs('educator.materials.*')],
        ['label' => 'Group Chats', 'url' => route('educator.chats.index'),     'active' => request()->routeIs('educator.chats.*')],
        ['label' => 'Monitoring',  'url' => route('educator.monitoring.index'), 'active' => request()->routeIs('educator.monitoring.*')],
    ];
@endphp

@extends('layouts.app', ['role' => 'educator', 'navItems' => $navItems])
