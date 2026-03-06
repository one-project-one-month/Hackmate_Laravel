<?php

use App\Http\Controllers\GithubSocialLoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProjectController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:api');
use App\Http\Controllers\AuthController;

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

    //Project Routes (Clean URL: /api/v1/projects)
    Route::middleware('auth:api')->group(function () {
        Route::get('/projects', [ProjectController::class, 'index']);
        Route::put('/projects/{id}', [ProjectController::class, 'update']);
        Route::put('/users/self-profile', [UserController::class, 'updateSelfUserInfo']);
}); 
});