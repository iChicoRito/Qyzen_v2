<?php

use App\Http\Controllers\Admin\AcademicTermController;
use App\Http\Controllers\Admin\AcademicYearController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\MessagingController;
use App\Http\Controllers\NotificationController;
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
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Student\ChatController as StudentChatController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\MaterialController as StudentMaterialController;
use App\Http\Controllers\Student\QuizController as StudentQuizController;
use App\Http\Controllers\Student\ScoreController as StudentScoreController;
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

// Stage H — Student features + quiz engine. Enrollment-gated; quiz actions add schedule +
// attempt gates. H6 server-side grading is the core invariant (correct_answer never client-side).
Route::middleware(['auth', 'verified', 'role:student'])
    ->prefix('student')->name('student.')->group(function () {
        Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');

        // H2 assessment list.
        Route::get('assessments', [StudentQuizController::class, 'index'])->name('assessments.index');
        Route::get('assessments/{assessment}', [StudentQuizController::class, 'details'])->name('assessments.details');

        // H3–H6 take-quiz: load, autosave draft, submit (server-side grading).
        Route::get('take-quiz/{assessment}', [StudentQuizController::class, 'take'])->name('take-quiz');
        Route::post('take-quiz/{assessment}/draft', [StudentQuizController::class, 'saveDraft'])
            ->middleware('throttle:quiz-writes')->name('take-quiz.draft');
        Route::post('take-quiz/{assessment}/submit', [StudentQuizController::class, 'submit'])
            ->middleware('throttle:quiz-writes')->name('take-quiz.submit');

        // H7 result/review + H8 scores history (own only).
        Route::get('scores', [StudentScoreController::class, 'index'])->name('scores.index');
        Route::get('scores/{score}', [StudentScoreController::class, 'show'])->name('scores.show');

        // H9 materials (enrollment-gated) + H10 chats (request/response).
        Route::get('materials', [StudentMaterialController::class, 'index'])->name('materials.index');
        Route::get('materials/{material}/download', [StudentMaterialController::class, 'download'])->name('materials.download');
        Route::get('chats', [StudentChatController::class, 'index'])->name('chats.index');
        Route::get('chats/{chat}', [StudentChatController::class, 'show'])->name('chats.show');
        Route::post('chats/{chat}/messages', [StudentChatController::class, 'sendMessage'])->name('chats.messages.send');
    });

// H11 — shared profile (all roles). Self-service column lock enforced in the Form Request.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Task 25 — notification bell read/delivery (owner-scoped; polling JSON, no live transport yet).
    // Shared group: the `role` middleware takes one role, and owner-scoping already isolates recipients.
    // read-all precedes {notification}/read so the literal segment isn't captured as an id.
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

    // Task 30 — private 1:1 student/educator messaging. Shared group: enrollment (subject-
    // agnostic) is the access boundary, re-checked by ConversationPolicy on every action.
    Route::get('/messaging/contacts', [MessagingController::class, 'contacts'])->name('messaging.contacts');
    Route::get('/messaging/conversations', [MessagingController::class, 'conversations'])->name('messaging.conversations');
    Route::post('/messaging/conversations', [MessagingController::class, 'store'])->name('messaging.conversations.store');
    Route::get('/messaging/conversations/{conversation}', [MessagingController::class, 'show'])->name('messaging.conversations.show');
    Route::post('/messaging/conversations/{conversation}/read', [MessagingController::class, 'markRead'])->name('messaging.conversations.read');
    Route::post('/messaging/conversations/{conversation}/messages', [MessagingController::class, 'sendMessage'])->name('messaging.messages.send');
    Route::put('/messaging/messages/{message}', [MessagingController::class, 'updateMessage'])->name('messaging.messages.update');
    Route::delete('/messaging/messages/{message}', [MessagingController::class, 'destroyMessage'])->name('messaging.messages.destroy');
});

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
        Route::get('enrollment/subject/{subject}', [EnrollmentController::class, 'showSubject'])->name('enrollment.subject');
        Route::resource('enrollment', EnrollmentController::class)->except('show')->parameters(['enrollment' => 'enrolled']);

        Route::resource('assessments', AssessmentController::class)->except('show');

        // G6 quizzes: bulk upload + delete-all-for-assessment.
        Route::get('quizzes/upload/template', [QuizController::class, 'uploadTemplate'])->name('quizzes.upload.template');
        Route::post('quizzes/upload', [QuizController::class, 'upload'])->name('quizzes.upload');
        Route::delete('quizzes/assessment/{assessment}', [QuizController::class, 'destroyForAssessment'])->name('quizzes.destroy-for-assessment');
        Route::resource('quizzes', QuizController::class)->except('show');

        // G7 scores (read-only) + grant retake.
        Route::get('scores', [ScoreController::class, 'index'])->name('scores.index');
        // G8/Task 27 export — registered above the {score} wildcard so these literal-prefix
        // routes aren't shadowed by it.
        Route::get('scores/export/preview/{assessment}', [ScoreController::class, 'exportPreview'])->name('scores.export.preview');
        Route::get('scores/export/{assessment}', [ScoreController::class, 'export'])->name('scores.export');
        Route::get('scores/export-bulk/run', [ScoreController::class, 'exportBulk'])->name('scores.export-bulk');
        Route::get('scores/{score}', [ScoreController::class, 'show'])->name('scores.show');
        Route::post('scores/retake', [ScoreController::class, 'grantRetake'])->name('scores.grant-retake');

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
