<?php

use App\Http\Controllers\Admin\AcademicTermController;
use App\Http\Controllers\Admin\AcademicYearController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\Educator\AssessmentController;
use App\Http\Controllers\Educator\ChatController;
use App\Http\Controllers\Educator\DashboardController as EducatorDashboardController;
use App\Http\Controllers\Educator\EnrollmentController;
use App\Http\Controllers\Educator\MaterialController;
use App\Http\Controllers\Educator\MonitoringController;
use App\Http\Controllers\Educator\QuizController;
use App\Http\Controllers\Educator\ScoreController;
use App\Http\Controllers\Educator\SectionController;
use App\Http\Controllers\Educator\SubjectController;
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

// Placeholder student dashboard (real pages land in Stage H), behind the D4 role middleware.
// Admin (Stage F) and educator (Stage G) are real groups below.
Route::get('/student/dashboard', fn () => view('dummy', [
    'role' => 'student',
    'navItems' => [['label' => 'Dashboard', 'url' => '#']],
]))->middleware(['auth', 'verified', 'role:student'])->name('student.dashboard');

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

// Stage G — Educator features. Ownership-gated: every list query carries visibleTo, every
// route a D-Policy. role:educator middleware bounces non-educators.
Route::middleware(['auth', 'verified', 'role:educator'])
    ->prefix('educator')->name('educator.')->group(function () {
        Route::get('/dashboard', [EducatorDashboardController::class, 'index'])->name('dashboard');

        Route::resource('sections', SectionController::class)->except('show');
        Route::resource('subjects', SubjectController::class)->except('show');

        // G4 enrollment: bulk import routes precede the resource binding.
        Route::get('enrollment/import/template', [EnrollmentController::class, 'importTemplate'])->name('enrollment.import.template');
        Route::post('enrollment/import', [EnrollmentController::class, 'import'])->name('enrollment.import');
        Route::resource('enrollment', EnrollmentController::class)->except('show')->parameters(['enrollment' => 'enrolled']);

        Route::resource('assessments', AssessmentController::class)->except('show');

        // G6 quizzes: bulk upload + delete-all-for-assessment.
        Route::get('quizzes/upload/template', [QuizController::class, 'uploadTemplate'])->name('quizzes.upload.template');
        Route::post('quizzes/upload', [QuizController::class, 'upload'])->name('quizzes.upload');
        Route::delete('quizzes/assessment/{assessment}', [QuizController::class, 'destroyForAssessment'])->name('quizzes.destroy-for-assessment');
        Route::resource('quizzes', QuizController::class)->except('show');

        // G7 scores (read-only) + grant retake.
        Route::get('scores', [ScoreController::class, 'index'])->name('scores.index');
        Route::get('scores/{score}', [ScoreController::class, 'show'])->name('scores.show');
        Route::post('scores/retake', [ScoreController::class, 'grantRetake'])->name('scores.grant-retake');
        // G8 export.
        Route::get('scores/export/{assessment}', [ScoreController::class, 'export'])->name('scores.export');
        Route::get('scores/export-bulk/run', [ScoreController::class, 'exportBulk'])->name('scores.export-bulk');

        // G9 materials + signed download.
        Route::get('materials/{material}/download', [MaterialController::class, 'download'])->name('materials.download');
        Route::resource('materials', MaterialController::class)->except('show');

        // G10 group chats (request/response).
        Route::get('chats', [ChatController::class, 'index'])->name('chats.index');
        Route::post('chats', [ChatController::class, 'store'])->name('chats.store');
        Route::get('chats/{chat}', [ChatController::class, 'show'])->name('chats.show');
        Route::delete('chats/{chat}', [ChatController::class, 'destroy'])->name('chats.destroy');
        Route::post('chats/{chat}/messages', [ChatController::class, 'sendMessage'])->name('chats.messages.send');

        // G11 monitoring (request/response).
        Route::get('monitoring', [MonitoringController::class, 'index'])->name('monitoring.index');
    });
