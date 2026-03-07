<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;
use App\Models\User;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();

        Project::factory()
            ->count(50)
            ->when(
                $user !== null,
                fn ($factory) => $factory->state([
                    'created_by_user_id' => $user->id,
                ])
            )
            ->create();
    }
}
