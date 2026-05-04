<?php

use App\Http\Controllers\TestController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserSubmittedQuestionController;
use App\Http\Controllers\QuestionReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/**
 * ==================== PUBLIC API ENDPOINTS (Authenticated) ====================
 */
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    // Tests
    Route::prefix('tests')->group(function () {
        Route::get('/', [TestController::class, 'apiIndex'])->name('api.tests.index');
        Route::get('/{test}', [TestController::class, 'apiShow'])->name('api.tests.show');
        Route::post('/{test}/answer', [TestController::class, 'apiAnswer'])->name('api.tests.answer');
        Route::post('/{test}/finish', [TestController::class, 'apiFinish'])->name('api.tests.finish');
    });

    // Subjects
    Route::prefix('subjects')->group(function () {
        Route::get('/', [SubjectController::class, 'apiIndex'])->name('api.subjects.index');
        Route::get('/{subject}', [SubjectController::class, 'apiShow'])->name('api.subjects.show');
    });

    // Leaderboard
    Route::prefix('leaderboard')->group(function () {
        Route::get('/', [LeaderboardController::class, 'apiIndex'])->name('api.leaderboard.index');
        Route::get('/subject/{subject}', [LeaderboardController::class, 'apiSubject'])->name('api.leaderboard.subject');
    });

    // Profile
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'apiShow'])->name('api.profile.show');
        Route::patch('/', [ProfileController::class, 'apiUpdate'])->name('api.profile.update');
    });

    // User Submitted Questions
    Route::prefix('questions')->group(function () {
        Route::get('/submissions', [UserSubmittedQuestionController::class, 'apiMySubmissions'])->name('api.questions.submissions');
        Route::post('/submit', [UserSubmittedQuestionController::class, 'apiStore'])->name('api.questions.store');
    });

    // Question Reports (Disputes)
    Route::prefix('reports')->group(function () {
        Route::get('/mine', [QuestionReportController::class, 'apiMine'])->name('api.reports.mine');
        Route::post('/', [QuestionReportController::class, 'apiStore'])->name('api.reports.store');
        Route::get('/pending', [QuestionReportController::class, 'apiPending'])->name('api.reports.pending');
        Route::post('/{report}/review', [QuestionReportController::class, 'apiApprove'])->name('api.reports.review');
    });
});
