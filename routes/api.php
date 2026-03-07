<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GithubSocialLoginController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:api');
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::get('/github/login-url', [GithubSocialLoginController::class, 'getLoginUrl']);
    Route::get('/github/callback', [GithubSocialLoginController::class, 'callback']);
    Route::get('/users/{id}', [UserController::class, 'getUserById']);
    Route::get('/projects', [ProjectController::class, 'index']);

    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
        Route::middleware('auth:api')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/refresh', [AuthController::class, 'refresh']);
            Route::post('/logout', [AuthController::class, 'logout']);

        });
    });

    // Project Routes (Clean URL: /api/v1/projects)
    Route::middleware('auth:api')->group(function () {
        Route::get('/feed', [ProjectController::class, 'feed']);
        Route::post('/projects', [ProjectController::class, 'store']);
        Route::get('/projects', [ProjectController::class, 'index']);
        Route::put('/projects/{id}', [ProjectController::class, 'update']);
    });
});
