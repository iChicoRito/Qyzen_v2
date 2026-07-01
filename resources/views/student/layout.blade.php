{{-- Student shell: Metronic light-sidebar layout + student nav (icons per item). --}}
@php
    $navItems = [
        ['heading' => 'Student'],
        ['label' => 'Dashboard',   'url' => route('student.dashboard'),         'active' => request()->routeIs('student.dashboard'),                                     'icon' => 'element-11'],
        ['label' => 'Assessments', 'url' => route('student.assessments.index'), 'active' => request()->routeIs('student.assessments.*') || request()->routeIs('student.take-quiz*'), 'icon' => 'questionnaire-tablet'],
        ['label' => 'My Scores',   'url' => route('student.scores.index'),      'active' => request()->routeIs('student.scores.*'),                                      'icon' => 'chart-simple'],
        ['heading' => 'Resources'],
        ['label' => 'Materials',   'url' => route('student.materials.index'),   'active' => request()->routeIs('student.materials.*'),                                   'icon' => 'folder'],
        ['label' => 'Chats',       'url' => route('student.chats.index'),       'active' => request()->routeIs('student.chats.*'),                                       'icon' => 'message-text-2'],
        ['label' => 'Profile',     'url' => route('profile.edit'),              'active' => request()->routeIs('profile.*'),                                             'icon' => 'user'],
    ];
@endphp

@extends('layouts.app', ['role' => 'student', 'navItems' => $navItems])
