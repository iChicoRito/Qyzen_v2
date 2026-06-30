<?php

use App\Http\Controllers\Admin\AcademicTermController;
use App\Http\Controllers\Admin\AcademicYearController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
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

// Placeholder educator/student dashboards (real pages land in Stages G/H), behind the
// D4 role middleware: auth + verified + role:{role}. Admin is a real group below (Stage F).
foreach (['educator', 'student'] as $role) {
    Route::get("/{$role}/dashboard", fn () => view('dummy', [
        'role' => $role,
        'navItems' => [['label' => 'Dashboard', 'url' => '#']],
    ]))->middleware(['auth', 'verified', "role:{$role}"])->name("{$role}.dashboard");
}

// Stage F — Admin features. One route group per role (CONVENTIONS.md); every action behind
// a D-Policy via $this->authorize() in the controller.
Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // F3/F4 user-import + bulk routes must precede the {user} resource binding.
        Route::get('users/import/template', [UserController::class, 'importTemplate'])->name('users.import.template');
        Route::post('users/import', [UserController::class, 'import'])->name('users.import');
        Route::post('users/{user}/resend-verification', [UserController::class, 'resendVerification'])->name('users.resend-verification');
        Route::resource('users', UserController::class)->except(['edit', 'update']);
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');

        Route::resource('roles', RoleController::class);

        // F6 permissions: bulk store instead of single create.
        Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');
        Route::post('permissions', [PermissionController::class, 'store'])->name('permissions.store');
        Route::get('permissions/{permission}', [PermissionController::class, 'show'])->name('permissions.show');
        Route::get('permissions/{permission}/edit', [PermissionController::class, 'edit'])->name('permissions.edit');
        Route::put('permissions/{permission}', [PermissionController::class, 'update'])->name('permissions.update');
        Route::delete('permissions/{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy');

        Route::resource('academic-years', AcademicYearController::class);
        Route::resource('academic-terms', AcademicTermController::class);
    });
