<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GithubSocialLoginController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TechStackController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\AuthMiddleware;
use Illuminate\Http\Request;
// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:api');
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::get('/github/login-url', [GithubSocialLoginController::class, 'getLoginUrl']);
    Route::get('/github/callback', [GithubSocialLoginController::class, 'callback']);
    Route::get('/tech-stack', [TechStackController::class, 'index']);

    Route::get('/users/{id}', [UserController::class, 'getUserById']);

    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);

        Route::middleware(AuthMiddleware::class)->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/refresh', [AuthController::class, 'refresh']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    Route::prefix('user')->group(function () {
        Route::get('/{id}', [UserController::class, 'getUserById']);

        Route::middleware(AuthMiddleware::class)->group(function () {
            Route::put('/self-profile', [UserController::class, 'updateSelfUserInfo']);
        });
    });

    Route::middleware(AuthMiddleware::class)->group(function () {
        Route::get('/feed', [ProjectController::class, 'feed']);
        Route::post('/projects', [ProjectController::class, 'store']);
        Route::get('/projects', [ProjectController::class, 'index']);
        Route::get('/projects/own', [ProjectController::class, 'own']);
        Route::put('/projects/{id}', [ProjectController::class, 'update']);
        Route::delete('/projects/{id}', [ProjectController::class, 'destroy']);
    });
});
