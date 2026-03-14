<?php

use App\Http\Middleware\AuthMiddleware;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Route::middleware(AuthMiddleware::class)->get('/_test/auth-middleware', function () {
        return response()->json(['ok' => true]);
    });
});

it('blocks unauthenticated requests', function (): void {
    $this->getJson('/_test/auth-middleware')
        ->assertStatus(401)
        ->assertJson(['message' => 'Unauthenticated.']);
});

it('allows authenticated requests', function (): void {
    config(['auth.guards.api.driver' => 'session']);

    $user = User::factory()->create();

    $this->actingAs($user, 'api')
        ->getJson('/_test/auth-middleware')
        ->assertOk()
        ->assertJson(['ok' => true]);
});
