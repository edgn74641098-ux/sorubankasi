<?php

use App\Http\Controllers\Admin\QuestionController as AdminQuestionController;
use App\Http\Controllers\Admin\QuestionImportController;
use App\Http\Controllers\Admin\QuestionReportController as AdminQuestionReportController;
use App\Http\Controllers\Admin\QuestionVersionController;
use App\Http\Controllers\Admin\ArchiveController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\SearchController as AdminSearchController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SubjectController as AdminSubjectController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuestionReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\UserSubmittedQuestionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', HomeController::class);
Route::get('/health', HealthController::class)->name('health');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified', 'system.available'])
    ->name('dashboard');

Route::get('/subjects', [SubjectController::class, 'index'])
    ->middleware(['auth', 'verified', 'system.available'])
    ->name('subjects.index');

Route::middleware(['auth', 'verified', 'role:admin,editor'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::get('search', AdminSearchController::class)->name('search');
        Route::get('archive', [ArchiveController::class, 'index'])->name('archive.index');
        Route::post('archive/subjects/restore', [ArchiveController::class, 'restoreSubjects'])->name('archive.subjects.restore-bulk');
        Route::post('archive/questions/restore', [ArchiveController::class, 'restoreQuestions'])->name('archive.questions.restore-bulk');
        Route::delete('archive/subjects/remove', [ArchiveController::class, 'removeSubjects'])->name('archive.subjects.remove-bulk');
        Route::delete('archive/questions/remove', [ArchiveController::class, 'removeQuestions'])->name('archive.questions.remove-bulk');
        Route::post('archive/subjects/{subject}/restore', [ArchiveController::class, 'restoreSubject'])->name('archive.subjects.restore');
        Route::post('archive/questions/{question}/restore', [ArchiveController::class, 'restoreQuestion'])->name('archive.questions.restore');
        Route::delete('archive/subjects/{subject}/remove', [ArchiveController::class, 'removeSubject'])->name('archive.subjects.remove');
        Route::delete('archive/questions/{question}/remove', [ArchiveController::class, 'removeQuestion'])->name('archive.questions.remove');

        Route::resource('subjects', AdminSubjectController::class)
            ->except(['show']);

        Route::resource('questions', AdminQuestionController::class)
            ->except(['show']);
        Route::post('questions/archive-bulk', [AdminQuestionController::class, 'archiveBulk'])->name('questions.archive-bulk');
        Route::get('questions/{question}/versions', [QuestionVersionController::class, 'index'])->name('questions.versions.index');
        Route::post('questions/{question}/versions/{version}/rollback', [QuestionVersionController::class, 'rollback'])->name('questions.versions.rollback');

        Route::get('reports', [AdminQuestionReportController::class, 'index'])->name('reports.index');
        Route::post('reports/{report}/approve', [AdminQuestionReportController::class, 'approve'])->name('reports.approve');
        Route::post('reports/{report}/reject', [AdminQuestionReportController::class, 'reject'])->name('reports.reject');

        Route::get('imports', [QuestionImportController::class, 'index'])->name('imports.index');
        Route::get('imports/template/download', [QuestionImportController::class, 'downloadTemplate'])->name('imports.template.download');
        Route::post('imports', [QuestionImportController::class, 'store'])->name('imports.store');
        Route::get('imports/{import}', [QuestionImportController::class, 'show'])->name('imports.show');
        Route::post('imports/{import}/confirm', [QuestionImportController::class, 'confirm'])->name('imports.confirm');
        Route::delete('imports/{import}', [QuestionImportController::class, 'destroy'])->name('imports.destroy');

        Route::middleware('role:admin')->group(function () {
            Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
            Route::patch('users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.update-role');
            Route::patch('users/{user}/status', [AdminUserController::class, 'updateStatus'])->name('users.update-status');
            Route::delete('users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

            Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
            Route::put('settings', [SettingsController::class, 'update'])->middleware('reconfirm.password')->name('settings.update');

            Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        });

        // User submitted questions (moderation)
        Route::get('submissions', [UserSubmittedQuestionController::class, 'pendingReview'])->name('submissions.pending');
        Route::post('submissions/{submission}/approve', [UserSubmittedQuestionController::class, 'approve'])->name('submissions.approve');
        Route::post('submissions/{submission}/reject', [UserSubmittedQuestionController::class, 'reject'])->name('submissions.reject');
        Route::post('submissions/{submission}/revoke', [UserSubmittedQuestionController::class, 'revokeApproval'])->name('submissions.revoke');
    });

Route::middleware(['auth', 'system.available'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', 'system.available'])->group(function () {
    Route::get('/tests/start', [TestController::class, 'create'])->name('tests.create');
    Route::post('/tests/start', [TestController::class, 'start'])->name('tests.start');
    Route::get('/tests/{test}', [TestController::class, 'show'])->name('tests.show');
    Route::post('/tests/{test}/answer', [TestController::class, 'answer'])->name('tests.answer');
    Route::post('/tests/{test}/finish', [TestController::class, 'finish'])->name('tests.finish');
    Route::get('/tests/{test}/review', [TestController::class, 'review'])->name('tests.review');
    Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard.index');
    Route::get('/search', SearchController::class)->name('search.index');

    // User submitted questions
    Route::get('/questions/submit', [UserSubmittedQuestionController::class, 'create'])->name('questions.create');
    Route::post('/questions/submit', [UserSubmittedQuestionController::class, 'store'])->name('questions.store');
    Route::get('/questions/my-submissions', [UserSubmittedQuestionController::class, 'myQuestions'])->name('questions.submitted');
    Route::post('/questions/{question}/report-unnecessary', [UserSubmittedQuestionController::class, 'reportUnnecessary'])->name('questions.report-unnecessary');
    Route::post('/questions/report', [QuestionReportController::class, 'store'])->name('questions.report');
    Route::get('/questions/my-reports', [QuestionReportController::class, 'myReports'])->name('questions.reports');
});

require __DIR__.'/auth.php';
