<?php

use App\Http\Controllers\Auth\OAuthController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check() ? redirect()->route('dashboard.redirect') : view('welcome');
});

// C3: Google OAuth (Socialite)
Route::get('/oauth/{provider}/redirect', [OAuthController::class, 'redirect'])->name('oauth.redirect');
Route::get('/oauth/{provider}/callback', [OAuthController::class, 'callback'])->name('oauth.callback');

// C5: post-login bounce to the role dashboard (admin > educator > student).
Route::get('/dashboard', fn () => redirect(Auth::user()->dashboardPath()))
    ->middleware(['auth', 'verified'])->name('dashboard.redirect');

// Placeholder role dashboards (real pages land in Stages F/G/H). Role middleware = Stage D.
foreach (['admin', 'educator', 'student'] as $role) {
    Route::get("/{$role}/dashboard", fn () => view('dummy', [
        'role' => $role,
        'navItems' => [['label' => 'Dashboard', 'url' => '#']],
    ]))->middleware(['auth', 'verified'])->name("{$role}.dashboard");
}

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
