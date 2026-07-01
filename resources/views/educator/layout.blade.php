{{-- Educator shell: Metronic light-sidebar layout + educator nav (icons per item). --}}
@php
    $navItems = [
        ['heading' => 'Educator'],
        ['label' => 'Dashboard',   'url' => route('educator.dashboard'),         'active' => request()->routeIs('educator.dashboard'),                                    'icon' => 'element-11'],
        ['heading' => 'Classroom'],
        ['label' => 'Sections',    'url' => route('educator.sections.index'),    'active' => request()->routeIs('educator.sections.*'),                                   'icon' => 'abstract-26'],
        ['label' => 'Subjects',    'url' => route('educator.subjects.index'),    'active' => request()->routeIs('educator.subjects.*'),                                   'icon' => 'book'],
        ['label' => 'Enrollment',  'url' => route('educator.enrollment.index'),  'active' => request()->routeIs('educator.enrollment.*'),                                 'icon' => 'people'],
        ['heading' => 'Assessment'],
        ['label' => 'Assessments', 'url' => route('educator.assessments.index'), 'active' => request()->routeIs('educator.assessments.*'),                                'icon' => 'questionnaire-tablet'],
        ['label' => 'Quizzes',     'url' => route('educator.quizzes.index'),     'active' => request()->routeIs('educator.quizzes.*'),                                    'icon' => 'note-2'],
        ['label' => 'Scores',      'url' => route('educator.scores.index'),      'active' => request()->routeIs('educator.scores.*'),                                     'icon' => 'chart-simple'],
        ['heading' => 'Resources'],
        ['label' => 'Materials',   'url' => route('educator.materials.index'),   'active' => request()->routeIs('educator.materials.*'),                                  'icon' => 'folder'],
        ['label' => 'Group Chats', 'url' => route('educator.chats.index'),       'active' => request()->routeIs('educator.chats.*'),                                      'icon' => 'message-text-2'],
        ['label' => 'Monitoring',  'url' => route('educator.monitoring.index'),  'active' => request()->routeIs('educator.monitoring.*'),                                 'icon' => 'screen'],
    ];
@endphp

@extends('layouts.app', ['role' => 'educator', 'navItems' => $navItems])
