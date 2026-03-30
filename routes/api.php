<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\GithubSocialLoginController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\TechStackController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileSetupController;
use App\Http\Middleware\AuthMiddleware;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::get('/github/login-url', [GithubSocialLoginController::class, 'getLoginUrl']);
    Route::get('/github/callback', [GithubSocialLoginController::class, 'callback']);
    Route::get('/tech-stack', [TechStackController::class, 'index']);

    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);

        Route::middleware(AuthMiddleware::class)->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/refresh', [AuthController::class, 'refresh']);
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/profile/setup', [ProfileSetupController::class, 'store']);
        });
    });

    Route::middleware('auth:api')->group(function () {
        Route::get('/users/me', [UserController::class, 'getSelfProfile']);
        Route::put('/users/me', [UserController::class, 'updateSelfUserInfo']);

        Route::get('/feed', [FeedController::class, 'feed']);
        Route::post('/feed/metric/like', [FeedController::class, 'metricLike']);
        Route::post('/feed/metric/dislike', [FeedController::class, 'metricDislike']);

        Route::post('/projects', [ProjectController::class, 'store']);

        Route::get('/projects/own', [ProjectController::class, 'own']);
        Route::put('/projects/{id}', [ProjectController::class, 'update']);
        Route::delete('/projects/{id}', [ProjectController::class, 'destroy']);

        // Replace index with FeedController delegation
        Route::get('/projects', [ProjectController::class, 'index']);

        Route::post('/requests/send/{project_id}', [RequestController::class, 'send']);
        Route::post('/join-requests/{id}/approve', [RequestController::class, 'approve']);
        Route::post('/join-requests/{id}/disapprove', [RequestController::class, 'disapprove']);
        Route::get('/projects/{projectId}/join-requests', [RequestController::class, 'list']);
        Route::get('/projects/{project_id}/requests', [ProjectController::class, 'listJoinRequests']);
    });

    Route::get('/users/{id}', [UserController::class, 'getUserById']);
});
