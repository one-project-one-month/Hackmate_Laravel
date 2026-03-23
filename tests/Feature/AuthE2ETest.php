<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config(['jwt.secret' => str_repeat('a', 64)]);
});

it('logs in and returns a bearer token payload', function (): void {
    $password = 'password123';

    User::factory()->create([
        'email' => 'auth@example.com',
        'password' => bcrypt($password),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'auth@example.com',
        'password' => $password,
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'message',
            'content' => [
                'access_token',
                'token_type',
                'expires_in',
            ],
            'status',
        ]);

    expect($response->json('content.token_type'))->toBe('bearer');
});

it('rejects invalid credentials', function (): void {
    User::factory()->create([
        'email' => 'auth@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'auth@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'success' => false,
            'message' => 'Unauthorized',
            'status' => 401,
        ]);
});

it('returns 401 json when accessing protected endpoint without token', function (): void {
    $this->getJson('/api/v1/auth/me')->assertStatus(401);
});

it('returns current user for valid bearer token', function (): void {
    $password = 'password123';

    $user = User::factory()->create([
        'email' => 'me@example.com',
        'password' => bcrypt($password),
    ]);

    $login = $this->postJson('/api/v1/auth/login', [
        'email' => 'me@example.com',
        'password' => $password,
    ])->assertOk();

    $token = $login->json('content.access_token');

    $me = $this->withToken($token)
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJson([
            'success' => true,
            'status' => 200,
        ]);

    expect($me->json('content.id'))->toBe($user->id);
    expect($me->json('content.email'))->toBe('me@example.com');
});

it('completes forgot-password to reset-password flow', function (): void {
    $currentPassword = 'password123';
    $newPassword = 'newpassword123';

    User::factory()->create([
        'email' => 'reset@example.com',
        'password' => bcrypt($currentPassword),
    ]);

    $forgot = $this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'reset@example.com',
    ]);

    $forgot->assertOk()
        ->assertJsonStructure(['message', 'content' => ['otp']]);

    $otp = (string) $forgot->json('content.otp');

    $this->postJson('/api/v1/auth/reset-password', [
        'email' => 'reset@example.com',
        'otp' => $otp,
        'password' => $newPassword,
        'password_confirmation' => $newPassword,
    ])->assertOk();

    $this->postJson('/api/v1/auth/login', [
        'email' => 'reset@example.com',
        'password' => $newPassword,
    ])->assertOk()->assertJsonStructure(['content' => ['access_token']]);
});

it('refreshes and logs out with a valid token', function (): void {
    $password = 'password123';

    User::factory()->create([
        'email' => 'token@example.com',
        'password' => bcrypt($password),
    ]);

    $login = $this->postJson('/api/v1/auth/login', [
        'email' => 'token@example.com',
        'password' => $password,
    ])->assertOk();

    $token = $login->json('content.access_token');

    $this->withToken($token)
        ->postJson('/api/v1/auth/refresh')
        ->assertOk()
        ->assertJsonStructure(['content' => ['access_token', 'token_type', 'expires_in']]);

    $this->withToken($token)
        ->postJson('/api/v1/auth/logout')
        ->assertOk()
        ->assertJson(['message' => 'Successfully logged out']);
});
