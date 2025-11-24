<?php

use App\Http\Controllers\Api\AttemptController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CertificateController;
use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\QuizController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        // Admin only routes
        Route::middleware('role:admin')->group(function () {
            Route::apiResource('users', UserController::class);
        });

        // Quiz Routes
        Route::apiResource('quizzes', QuizController::class);
        Route::post('quizzes/{quiz}/questions', [QuestionController::class, 'store']);
        Route::put('questions/{question}', [QuestionController::class, 'update']);
        Route::delete('questions/{question}', [QuestionController::class, 'destroy']);

        // Attempt Routes
        Route::post('quizzes/{quiz}/start', [AttemptController::class, 'start']);
        Route::post('attempts/{attempt}/submit', [AttemptController::class, 'submitAnswer']);
        Route::post('attempts/{attempt}/finish', [AttemptController::class, 'finish']);

        // Analytics Routes
        Route::get('quizzes/{quiz}/leaderboard', [LeaderboardController::class, 'index']);
        Route::get('quizzes/{quiz}/stats', [ReportController::class, 'quizStatistics']);

        // Certificate Routes
        Route::post('attempts/{attempt}/certificate', [CertificateController::class, 'generate']);
        Route::get('certificates/verify/{code}', [CertificateController::class, 'verify']);

        // Webhook Routes (Admin/Teacher only)
        Route::get('quizzes/{quiz}/webhooks', [WebhookController::class, 'index']);
        Route::post('quizzes/{quiz}/webhooks', [WebhookController::class, 'store']);
        Route::delete('webhooks/{webhook}', [WebhookController::class, 'destroy']);
    });
});
