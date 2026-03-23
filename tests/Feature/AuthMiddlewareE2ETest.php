<?php

use App\Http\Middleware\AuthMiddleware;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Route::middleware(AuthMiddleware::class)->get('/_test/auth-middleware', function () {
        return ApiResponse::success(['ok' => true]);
    });
});

it('blocks unauthenticated requests', function (): void {
    $this->getJson('/_test/auth-middleware')
        ->assertStatus(401)
        ->assertJson([
            'success' => false,
            'message' => 'Unauthenticated.',
            'status' => 401,
        ]);
});

it('allows authenticated requests', function (): void {
    config(['auth.guards.api.driver' => 'session']);

    $user = User::factory()->create();

    $this->actingAs($user, 'api')
        ->getJson('/_test/auth-middleware')
        ->assertOk()
        ->assertJson([
            'success' => true,
            'status' => 200,
        ])
        ->assertJsonPath('content.ok', true);
});
