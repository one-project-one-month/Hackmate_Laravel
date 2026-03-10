<?php

use App\Http\Controllers\GithubSocialLoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:api');
use App\Http\Controllers\AuthController;

Route::prefix('v1')->group(function () {

    Route::get('/github/login-url', [GithubSocialLoginController::class, 'getLoginUrl']);
    Route::get('/github/callback', [GithubSocialLoginController::class, 'callback']);
    
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::middleware('auth:api')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/refresh', [AuthController::class, 'refresh']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    Route::prefix('user')->group(function () {
        Route::get('/{id}', [UserController::class, 'getUserById']);
        Route::middleware('auth:api')->group(function () {
            Route::put('/self-profile', [UserController::class, 'updateSelfUserInfo']);
        });
    });

}); 