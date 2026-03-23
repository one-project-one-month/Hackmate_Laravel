<?php

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

    return $response->json('content.access_token');
}

it('completes profile setup with tech stacks only', function (): void {
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
        'has_profile_setup' => false,
    ]);

    $php = TechStack::query()->create([
        'name' => 'PHP',
        'category' => 'language',
    ]);

    $laravel = TechStack::query()->create([
        'name' => 'Laravel',
        'category' => 'framework',
    ]);

    $token = loginToken($user);

    $response = $this->withToken($token)
        ->postJson('/api/v1/auth/profile/setup', [
            'tech_stack' => [$php->id, $laravel->id],
        ])
        ->assertOk()
        ->assertJson([
            'message' => 'Profile setup complete',
        ]);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'has_profile_setup' => true,
    ]);

    $this->assertDatabaseHas('user_tech_stacks', [
        'user_id' => $user->id,
        'tech_stack_id' => $php->id,
    ]);

    $this->assertDatabaseHas('user_tech_stacks', [
        'user_id' => $user->id,
        'tech_stack_id' => $laravel->id,
    ]);

    expect($response->json('user.tech_stacks'))->toHaveCount(2);
});

it('completes profile setup with profile image', function (): void {
    Storage::fake('public');

    $user = User::factory()->create([
        'password' => bcrypt('password123'),
        'has_profile_setup' => false,
    ]);

    $techStack = TechStack::query()->create([
        'name' => 'PHP',
        'category' => 'language',
    ]);

    $token = loginToken($user);

    $file = UploadedFile::fake()->image('avatar.jpg');

    $response = $this->withToken($token)
        ->post('/api/v1/auth/profile/setup', [
            'tech_stack' => [$techStack->id],
            'profile_image' => $file,
        ])
        ->assertOk();

    $imagePath = $response->json('user.profile_image');

    expect($imagePath)->not->toBeNull();

    Storage::disk('public')->assertExists($imagePath);

    expect($response->json('profile_image_url'))->toContain('/storage/profile-images/');
});

it('requires authentication for profile setup', function (): void {
    $techStack = TechStack::query()->create([
        'name' => 'PHP',
        'category' => 'language',
    ]);

    $this->postJson('/api/v1/auth/profile/setup', [
        'tech_stack' => [$techStack->id],
    ])->assertStatus(401);
});

it('validates tech_stack is required', function (): void {
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
    ]);

    $token = loginToken($user);

    $this->withToken($token)
        ->postJson('/api/v1/auth/profile/setup', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors('tech_stack');
});

it('validates tech_stack must be an array', function (): void {
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
    ]);

    $token = loginToken($user);

    $this->withToken($token)
        ->postJson('/api/v1/auth/profile/setup', [
            'tech_stack' => 'invalid',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('tech_stack');
});

it('validates each tech_stack item exists', function (): void {
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
    ]);

    $token = loginToken($user);

    $this->withToken($token)
        ->postJson('/api/v1/auth/profile/setup', [
            'tech_stack' => [99999],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('tech_stack.0');
});

it('validates profile image must be an image', function (): void {
    Storage::fake('public');

    $user = User::factory()->create([
        'password' => bcrypt('password123'),
    ]);

    $techStack = TechStack::query()->create([
        'name' => 'Laravel',
        'category' => 'framework',
    ]);

    $token = loginToken($user);

    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    $this->withToken($token)
        ->post('/api/v1/auth/profile/setup', [
            'tech_stack' => [$techStack->id],
            'profile_image' => $file,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('profile_image');
});

it('validates profile image max size', function (): void {
    Storage::fake('public');

    $user = User::factory()->create([
        'password' => bcrypt('password123'),
    ]);

    $techStack = TechStack::query()->create([
        'name' => 'Vue',
        'category' => 'framework',
    ]);

    $token = loginToken($user);

    $file = UploadedFile::fake()->image('large.jpg')->size(3000);

    $this->withToken($token)
        ->post('/api/v1/auth/profile/setup', [
            'tech_stack' => [$techStack->id],
            'profile_image' => $file,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('profile_image');
});

it('syncs tech stacks and removes old ones on profile setup', function (): void {
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
    ]);

    $oldStack = TechStack::query()->create([
        'name' => 'Old Tech',
        'category' => 'tool',
    ]);

    $newStack = TechStack::query()->create([
        'name' => 'New Tech',
        'category' => 'tool',
    ]);

    $user->techStacks()->sync([$oldStack->id]);

    $token = loginToken($user);

    $this->withToken($token)
        ->postJson('/api/v1/auth/profile/setup', [
            'tech_stack' => [$newStack->id],
        ])
        ->assertOk();

    $this->assertDatabaseMissing('user_tech_stacks', [
        'user_id' => $user->id,
        'tech_stack_id' => $oldStack->id,
    ]);

    $this->assertDatabaseHas('user_tech_stacks', [
        'user_id' => $user->id,
        'tech_stack_id' => $newStack->id,
    ]);
});

it('returns null profile image url when no image uploaded', function (): void {
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
    ]);

    $techStack = TechStack::query()->create([
        'name' => 'Go',
        'category' => 'language',
    ]);

    $token = loginToken($user);

    $response = $this->withToken($token)
        ->postJson('/api/v1/auth/profile/setup', [
            'tech_stack' => [$techStack->id],
        ])
        ->assertOk();

    expect($response->json('profile_image_url'))->toBeNull();
});

it('loads tech stacks in profile setup response', function (): void {
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
    ]);

    $php = TechStack::query()->create([
        'name' => 'PHP',
        'category' => 'language',
    ]);

    $token = loginToken($user);

    $response = $this->withToken($token)
        ->postJson('/api/v1/auth/profile/setup', [
            'tech_stack' => [$php->id],
        ])
        ->assertOk();

    expect($response->json('user.tech_stacks.0.name'))->toBe('PHP');
    expect($response->json('user.tech_stacks.0.category'))->toBe('language');
});