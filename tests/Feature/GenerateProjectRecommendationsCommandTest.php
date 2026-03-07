<?php

use App\Models\Feed;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('generates feed rows from active project reaction scores', function (): void {
    $owner = User::factory()->create();

    $top = Project::query()->create([
        'title' => 'Top',
        'description' => 'Top score',
        'created_by_user_id' => $owner->id,
        'is_active' => true,
        'like_count' => 30,
        'dislike_count' => 2,
    ]);

    $mid = Project::query()->create([
        'title' => 'Mid',
        'description' => 'Mid score',
        'created_by_user_id' => $owner->id,
        'is_active' => true,
        'like_count' => 10,
        'dislike_count' => 4,
    ]);

    Project::query()->create([
        'title' => 'Inactive',
        'description' => 'Should not be in feed',
        'created_by_user_id' => $owner->id,
        'is_active' => false,
        'like_count' => 99,
        'dislike_count' => 0,
    ]);

    $this->artisan('app:generate-project-recommendations')->assertExitCode(0);

    expect(Feed::query()->count())->toBe(2);

    $first = Feed::query()->orderBy('rank')->first();
    $second = Feed::query()->orderBy('rank')->skip(1)->first();

    expect($first?->project_id)->toBe($top->id)
        ->and($first?->score)->toBe(28)
        ->and($first?->rank)->toBe(1)
        ->and($second?->project_id)->toBe($mid->id)
        ->and($second?->score)->toBe(6)
        ->and($second?->rank)->toBe(2);
});

it('rebuilds feed on rerun instead of appending stale rows', function (): void {
    $owner = User::factory()->create();

    $project = Project::query()->create([
        'title' => 'Dynamic',
        'description' => 'Changes over time',
        'created_by_user_id' => $owner->id,
        'is_active' => true,
        'like_count' => 5,
        'dislike_count' => 1,
    ]);

    $this->artisan('app:generate-project-recommendations')->assertExitCode(0);
    expect(Feed::query()->count())->toBe(1);
    expect(Feed::query()->first()?->score)->toBe(4);

    $project->update([
        'like_count' => 2,
        'dislike_count' => 3,
    ]);

    $this->artisan('app:generate-project-recommendations')->assertExitCode(0);

    expect(Feed::query()->count())->toBe(1);
    expect(Feed::query()->first()?->score)->toBe(-1);
});
