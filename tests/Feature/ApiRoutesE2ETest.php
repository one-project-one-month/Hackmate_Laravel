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

it('requires auth for feed route', function (): void {
    $this->getJson('/api/v1/feed')->assertStatus(401);
});

it('requires auth for own projects route', function (): void {
    $this->getJson('/api/v1/projects/own')->assertStatus(401);
});

it('returns only the authenticated users own projects', function (): void {
    $owner = User::factory()->create([
        'email' => 'owner-projects@example.com',
        'password' => bcrypt('password123'),
    ]);

    $other = User::factory()->create();

    $olderProject = Project::factory()->create([
        'created_by_user_id' => $owner->id,
        'title' => 'Older Owned Project',
        'created_at' => now()->subDay(),
        'updated_at' => now()->subDay(),
    ]);

    $newerProject = Project::factory()->create([
        'created_by_user_id' => $owner->id,
        'title' => 'Newer Owned Project',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Project::factory()->create([
        'created_by_user_id' => $other->id,
        'title' => 'Someone Else Project',
    ]);

    $token = loginToken($owner);

    $response = $this->withToken($token)->getJson('/api/v1/projects/own')->assertOk();

    expect($response->json())->toHaveCount(2);
    expect($response->json('0.id'))->toBe($newerProject->id);
    expect($response->json('1.id'))->toBe($olderProject->id);
    $response
        ->assertJsonMissingPath('0.like_count')
        ->assertJsonMissingPath('0.dislike_count');
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

it('forbids project deletion when user is not owner', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create([
        'email' => 'delete-other@example.com',
        'password' => bcrypt('password123'),
    ]);

    $project = Project::factory()->create([
        'created_by_user_id' => $owner->id,
    ]);

    $token = loginToken($other);

    $this->withToken($token)
        ->deleteJson('/api/v1/projects/'.$project->id)
        ->assertStatus(403)
        ->assertJsonPath('message', 'Unauthorized');

    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
    ]);
});

it('deletes a project when requested by the owner', function (): void {
    $owner = User::factory()->create([
        'email' => 'delete-owner@example.com',
        'password' => bcrypt('password123'),
    ]);

    $project = Project::factory()->create([
        'created_by_user_id' => $owner->id,
    ]);

    $token = loginToken($owner);

    $this->withToken($token)
        ->deleteJson('/api/v1/projects/'.$project->id)
        ->assertNoContent();

    $this->assertDatabaseMissing('projects', [
        'id' => $project->id,
    ]);
});

it('orders active projects using generated feed ranking', function (): void {
    $owner = User::factory()->create([
        'email' => 'ranking@example.com',
        'password' => bcrypt('password123'),
    ]);

    Project::query()->create([
        'title' => 'Top Ranked',
        'description' => 'High net score',
        'type' => 'web',
        'created_by_user_id' => $owner->id,
        'github_repo' => 'https://github.com/example/top',
        'is_active' => true,
        'like_count' => 20,
        'dislike_count' => 3,
    ]);

    Project::query()->create([
        'title' => 'Lower Ranked',
        'description' => 'Lower net score',
        'type' => 'web',
        'created_by_user_id' => $owner->id,
        'github_repo' => 'https://github.com/example/lower',
        'is_active' => true,
        'like_count' => 8,
        'dislike_count' => 5,
    ]);

    $this->artisan('app:generate-project-recommendations')->assertExitCode(0);

    $token = loginToken($owner);

    $response = $this->withToken($token)->getJson('/api/v1/feed')->assertOk();

    expect($response->json('0.title'))->toBe('Top Ranked');
    expect($response->json('1.title'))->toBe('Lower Ranked');
    $response
        ->assertJsonMissingPath('0.like_count')
        ->assertJsonMissingPath('0.dislike_count');
});

it('returns generated feed ordering from feed route', function (): void {
    $owner = User::factory()->create([
        'email' => 'feed@example.com',
        'password' => bcrypt('password123'),
    ]);

    Project::query()->create([
        'title' => 'Feed Top',
        'description' => 'Higher score',
        'type' => 'web',
        'created_by_user_id' => $owner->id,
        'github_repo' => 'https://github.com/example/feed-top',
        'is_active' => true,
        'like_count' => 12,
        'dislike_count' => 1,
    ]);

    Project::query()->create([
        'title' => 'Feed Low',
        'description' => 'Lower score',
        'type' => 'web',
        'created_by_user_id' => $owner->id,
        'github_repo' => 'https://github.com/example/feed-low',
        'is_active' => true,
        'like_count' => 3,
        'dislike_count' => 1,
    ]);

    $this->artisan('app:generate-project-recommendations')->assertExitCode(0);

    $token = loginToken($owner);
    $response = $this->withToken($token)->getJson('/api/v1/feed')->assertOk();

    expect($response->json('0.title'))->toBe('Feed Top');
    expect($response->json('1.title'))->toBe('Feed Low');
    $response
        ->assertJsonMissingPath('0.like_count')
        ->assertJsonMissingPath('0.dislike_count');
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

it('increments project dislike metric', function (): void {
    $owner = User::factory()->create([
        'email' => 'dislike@example.com',
        'password' => bcrypt('password123'),
    ]);

    $project = Project::factory()->create([
        'created_by_user_id' => $owner->id,
        'like_count' => 0,
        'dislike_count' => 0,
        'is_active' => true,
    ]);

    $token = loginToken($owner);

    $this->withToken($token)
        ->postJson('/api/v1/feed/metric/dislike', [
            'project_id' => $project->id,
        ])
        ->assertNoContent();

    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'dislike_count' => 1,
    ]);
});

it('increments project like metric', function (): void {
    $owner = User::factory()->create([
        'email' => 'like@example.com',
        'password' => bcrypt('password123'),
    ]);

    $project = Project::factory()->create([
        'created_by_user_id' => $owner->id,
        'like_count' => 0,
        'dislike_count' => 0,
        'is_active' => true,
    ]);

    $token = loginToken($owner);

    $this->withToken($token)
        ->postJson('/api/v1/feed/metric/like', [
            'project_id' => $project->id,
        ])
        ->assertNoContent();

    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'like_count' => 1,
    ]);
});