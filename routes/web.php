<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// ponytail: temporary layout-check route for Phase 0 (A4); remove once real role routes land
Route::get('/layout-check', function () {
    return view('dummy', [
        'role' => 'educator',
        'navItems' => [
            ['label' => 'Dashboard', 'url' => '#'],
            ['label' => 'Classroom', 'url' => '#'],
            ['label' => 'Assessments', 'url' => '#'],
        ],
    ]);
});
