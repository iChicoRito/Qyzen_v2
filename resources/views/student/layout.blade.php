{{-- Student shell: wraps the Metronic app layout with the student sidebar nav. --}}
@php
    $navItems = [
        ['label' => 'Dashboard',   'url' => route('student.dashboard'),        'active' => request()->routeIs('student.dashboard')],
        ['label' => 'Assessments', 'url' => route('student.assessments.index'), 'active' => request()->routeIs('student.assessments.*') || request()->routeIs('student.take-quiz*')],
        ['label' => 'My Scores',   'url' => route('student.scores.index'),     'active' => request()->routeIs('student.scores.*')],
        ['label' => 'Materials',   'url' => route('student.materials.index'),  'active' => request()->routeIs('student.materials.*')],
        ['label' => 'Chats',       'url' => route('student.chats.index'),      'active' => request()->routeIs('student.chats.*')],
        ['label' => 'Profile',     'url' => route('profile.edit'),             'active' => request()->routeIs('profile.*')],
    ];
@endphp

@extends('layouts.app', ['role' => 'student', 'navItems' => $navItems])
