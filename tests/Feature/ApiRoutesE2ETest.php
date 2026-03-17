<?php

use App\Models\Project;
use App\Models\ProjectRole;
use App\Models\TechStack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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

it('stores project with image', function (): void {
    Storage::fake('public');

    $user = User::factory()->create([
        'password' => bcrypt('password123'),
    ]);

    $token = loginToken($user);

    $file = UploadedFile::fake()->image('project.jpg');

    $response = $this->withToken($token)
        ->post('/api/v1/projects', [
            'title' => 'Project with Image',
            'description' => 'This is a valid description',
            'image' => $file,
        ])
        ->assertCreated();

    $imagePath = $response->json('image');

    Storage::disk('public')->assertExists($imagePath);
});

it('stores project with required roles as labels', function (): void {
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
    ]);

    $token = loginToken($user);

    $response = $this->withToken($token)
        ->postJson('/api/v1/projects', [
            'title' => 'Project with Roles',
            'description' => 'This is a valid description',
            'required_roles' => ['backend', 'designer'],
        ])
        ->assertCreated();

    $projectId = $response->json('id');

    $backendRoleId = ProjectRole::query()->where('label', 'backend')->value('id');
    $designerRoleId = ProjectRole::query()->where('label', 'designer')->value('id');

    expect($backendRoleId)->not->toBeNull();
    expect($designerRoleId)->not->toBeNull();

    $this->assertDatabaseHas('project_required_roles', [
        'project_id' => $projectId,
        'role_id' => $backendRoleId,
    ]);

    $this->assertDatabaseHas('project_required_roles', [
        'project_id' => $projectId,
        'role_id' => $designerRoleId,
    ]);
});

it('adds required roles on update without removing existing ones', function (): void {
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
    ]);

    $project = Project::factory()->create([
        'created_by_user_id' => $user->id,
    ]);

    $existingRole = ProjectRole::query()->create(['label' => 'frontend']);
    $project->requiredRoles()->sync([$existingRole->id]);

    $token = loginToken($user);

    $this->withToken($token)
        ->putJson('/api/v1/projects/'.$project->id, [
            'required_roles' => ['backend'],
        ])
        ->assertOk();

    $backendRoleId = ProjectRole::query()->where('label', 'backend')->value('id');

    expect($backendRoleId)->not->toBeNull();

    $this->assertDatabaseHas('project_required_roles', [
        'project_id' => $project->id,
        'role_id' => $existingRole->id,
    ]);

    $this->assertDatabaseHas('project_required_roles', [
        'project_id' => $project->id,
        'role_id' => $backendRoleId,
    ]);
});

it('updates project image', function (): void {
    Storage::fake('public');

    $user = User::factory()->create([
        'password' => bcrypt('password123'),
    ]);

    $project = Project::factory()->create([
        'created_by_user_id' => $user->id,
    ]);

    $token = loginToken($user);

    $file = UploadedFile::fake()->image('new.jpg');

    $response = $this->withToken($token)
        ->put('/api/v1/projects/'.$project->id, [
            'image' => $file,
        ])
        ->assertOk();

    $imagePath = $response->json('image');

    Storage::disk('public')->assertExists($imagePath);
});

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

it('returns all tech stacks', function (): void {
    $php = TechStack::query()->create([
        'name' => 'PHP',
        'category' => 'language',
    ]);

    $laravel = TechStack::query()->create([
        'name' => 'Laravel',
        'category' => 'framework',
    ]);

    $response = $this->getJson('/api/v1/tech-stack')
        ->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'successful',
            'status' => 200,
        ]);

    expect($response->json('content'))->toHaveCount(2);
    expect($response->json('content.0.id'))->toBe($laravel->id);
    expect($response->json('content.0.name'))->toBe('Laravel');
    expect($response->json('content.0.category'))->toBe('framework');
    expect($response->json('content.1.id'))->toBe($php->id);
});

it('requires auth for projects index route', function (): void {
    $this->getJson('/api/v1/projects')->assertStatus(401);
});

it('returns only the authenticated users own projects', function (): void {
    $owner = User::factory()->create([
        'email' => 'owner-projects@example.com',
        'password' => bcrypt('password123'),
    ]);

    $other = User::factory()->create();

    $olderProject = Project::factory()->create([
        'created_by_user_id' => $owner->id,
        'created_at' => now()->subDay(),
    ]);

    $newerProject = Project::factory()->create([
        'created_by_user_id' => $owner->id,
        'created_at' => now(),
    ]);

    Project::factory()->create([
        'created_by_user_id' => $other->id,
    ]);

    $token = loginToken($owner);

    $response = $this->withToken($token)
        ->getJson('/api/v1/projects/own')
        ->assertOk();

    expect($response->json())->toHaveCount(2);
    expect($response->json('0.id'))->toBe($newerProject->id);
    expect($response->json('1.id'))->toBe($olderProject->id);
});

it('updates project when authenticated owner sends valid data', function (): void {
    $owner = User::factory()->create([
        'password' => bcrypt('password123'),
    ]);

    $project = Project::factory()->create([
        'created_by_user_id' => $owner->id,
    ]);

    $token = loginToken($owner);

    $this->withToken($token)
        ->putJson('/api/v1/projects/'.$project->id, [
            'title' => 'Updated Title',
        ])
        ->assertOk()
        ->assertJsonPath('title', 'Updated Title');
});

it('deletes a project when requested by the owner', function (): void {
    $owner = User::factory()->create([
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

it('increments project like metric', function (): void {
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
    ]);

    $project = Project::factory()->create([
        'created_by_user_id' => $user->id,
        'like_count' => 0,
    ]);

    $token = loginToken($user);

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

it('increments project dislike metric', function (): void {
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
    ]);

    $project = Project::factory()->create([
        'created_by_user_id' => $user->id,
        'dislike_count' => 0,
    ]);

    $token = loginToken($user);

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
