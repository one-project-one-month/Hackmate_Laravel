<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\App\Models\Project>
     */
    protected $model = Project::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'type' => fake()->randomElement(['web', 'mobile', 'backend', 'ai']),
            'created_by_user_id' => User::factory(),
            'github_repo' => fake()->boolean(70) ? fake()->url() : null,
            'is_active' => true,
            'like_count' => fake()->numberBetween(0, 200),
            'dislike_count' => fake()->numberBetween(0, 100),
        ];
    }
}
