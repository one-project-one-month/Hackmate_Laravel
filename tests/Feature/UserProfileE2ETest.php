<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config(['jwt.secret' => str_repeat('a', 64)]);
});

it('updates the authenticated user profile with tech stacks as string array', function (): void {
    $user = User::factory()->create([
        'email' => 'profile@example.com',
        'password' => bcrypt('password123'),
    ]);

    $login = $this->postJson('/api/v1/auth/login', [
        'email' => 'profile@example.com',
        'password' => 'password123',
    ])->assertOk();

    $token = $login->json('content.access_token');

    $response = $this->withToken($token)
        ->putJson('/api/v1/users/me', [
            'name' => 'Updated Name',
            'preferred_role' => 'Backend Developer',
            'bio' => 'Building APIs with Laravel',
            'github_username' => 'updated-user',
            'tech_stacks' => ['Laravel', 'PostgreSQL', 'Docker'],
        ])
        ->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Profile updated successfully',
            'status' => 200,
        ]);

    expect($response->json('content.tech_stacks'))->toBe(['Laravel', 'PostgreSQL', 'Docker']);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Updated Name',
        'preferred_role' => 'Backend Developer',
        'github_username' => 'updated-user',
    ]);

    expect($user->fresh()->tech_stacks)->toBe(['Laravel', 'PostgreSQL', 'Docker']);
});

it('rejects duplicate values in tech stacks array', function (): void {
    $user = User::factory()->create([
        'email' => 'profile@example.com',
        'password' => bcrypt('password123'),
    ]);

    $login = $this->postJson('/api/v1/auth/login', [
        'email' => 'profile@example.com',
        'password' => 'password123',
    ])->assertOk();

    $token = $login->json('content.access_token');

    $this->withToken($token)
        ->putJson('/api/v1/users/me', [
            'name' => 'Updated Name',
            'tech_stacks' => ['Laravel', 'Laravel'],
        ])
        ->assertStatus(422);
});
