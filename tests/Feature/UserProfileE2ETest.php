<?php

use App\Models\JoinRequest;
use App\Models\Project;
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

it('returns the authenticated self profile with metrics', function (): void {
    $user = User::factory()->create([
        'email' => 'self@example.com',
        'password' => bcrypt('password123'),
        'name' => 'Self User',
    ]);

    $ownedProject = Project::factory()->create([
        'created_by_user_id' => $user->id,
    ]);

    $joinedProject = Project::factory()->create();
    $user->joinedProjects()->attach($joinedProject->id);

    JoinRequest::query()->create([
        'project_id' => $ownedProject->id,
        'user_id' => User::factory()->create()->id,
        'status' => 'pending',
    ]);

    JoinRequest::query()->create([
        'project_id' => $joinedProject->id,
        'user_id' => $user->id,
        'status' => 'approved',
    ]);

    $login = $this->postJson('/api/v1/auth/login', [
        'email' => 'self@example.com',
        'password' => 'password123',
    ])->assertOk();

    $token = $login->json('content.access_token');

    $response = $this->withToken($token)
        ->getJson('/api/v1/users/me')
        ->assertOk()
        ->assertJson([
            'success' => true,
            'status' => 200,
            'content' => [
                'email' => 'self@example.com',
                'name' => 'Self User',
            ],
        ]);

    expect($response->json('content.metrics.created_projects_count'))->toBe(1);
    expect($response->json('content.metrics.joined_projects_count'))->toBe(1);
    expect($response->json('content.metrics.pending_join_requests_count'))->toBe(1);
    expect($response->json('content.metrics.approved_join_requests_count'))->toBe(1);
});
