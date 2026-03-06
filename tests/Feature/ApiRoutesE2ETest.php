<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config(['jwt.secret' => str_repeat('a', 64)]);
});

function loginToken(User $user, string $password = 'password123'): string
{
    $response = test()->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => $password,
    ])->assertOk();

    return $response->json('access_token');
}

it('returns user by id', function (): void {
    $user = User::factory()->create();

    $this->getJson('/api/v1/users/'.$user->id)
        ->assertOk()
        ->assertJson([
            'success' => true,
            'status' => 200,
        ])
        ->assertJsonPath('content.id', $user->id);
});

it('returns 404 when user id does not exist', function (): void {
    $this->getJson('/api/v1/users/999999')
        ->assertStatus(404)
        ->assertJson([
            'success' => false,
            'message' => 'User not found',
            'status' => 404,
        ]);
});

it('requires auth for projects index route', function (): void {
    $this->getJson('/api/v1/projects')->assertStatus(401);
});

it('returns only active projects for authenticated user', function (): void {
    $owner = User::factory()->create([
        'email' => 'projects@example.com',
        'password' => bcrypt('password123'),
    ]);

    Project::query()->create([
        'title' => 'Active Project',
        'description' => 'Active',
        'type' => 'web',
        'created_by_user_id' => $owner->id,
        'github_repo' => 'https://github.com/example/active',
        'is_active' => true,
    ]);

    Project::query()->create([
        'title' => 'Inactive Project',
        'description' => 'Inactive',
        'type' => 'web',
        'created_by_user_id' => $owner->id,
        'github_repo' => 'https://github.com/example/inactive',
        'is_active' => false,
    ]);

    $token = loginToken($owner);

    $response = $this->withToken($token)->getJson('/api/v1/projects')->assertOk();

    expect($response->json())->toHaveCount(1);
    expect($response->json('0.title'))->toBe('Active Project');
});

it('forbids project update when user is not owner', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create([
        'email' => 'other@example.com',
        'password' => bcrypt('password123'),
    ]);

    $project = Project::query()->create([
        'title' => 'Owner Project',
        'description' => 'Owned by owner',
        'type' => 'web',
        'created_by_user_id' => $owner->id,
        'github_repo' => 'https://github.com/example/owner',
        'is_active' => true,
    ]);

    $token = loginToken($other);

    $this->withToken($token)
        ->putJson('/api/v1/projects/'.$project->id, [
            'title' => 'Attempted Update',
        ])
        ->assertStatus(403)
        ->assertJsonPath('message', 'Unauthorized');
});

it('updates project when authenticated owner sends valid data', function (): void {
    $owner = User::factory()->create([
        'email' => 'owner@example.com',
        'password' => bcrypt('password123'),
    ]);

    $project = Project::query()->create([
        'title' => 'Initial Title',
        'description' => 'Initial description',
        'type' => 'web',
        'created_by_user_id' => $owner->id,
        'github_repo' => 'https://github.com/example/initial',
        'is_active' => true,
    ]);

    $token = loginToken($owner);

    $this->withToken($token)
        ->putJson('/api/v1/projects/'.$project->id, [
            'title' => 'Updated Title',
            'description' => 'Updated description',
            'type' => 'mobile',
            'github_repo' => 'https://github.com/example/updated',
            'is_active' => true,
        ])
        ->assertOk()
        ->assertJsonPath('title', 'Updated Title');

    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'title' => 'Updated Title',
        'type' => 'mobile',
    ]);
});
