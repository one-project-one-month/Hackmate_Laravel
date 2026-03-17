<?php

namespace Database\Seeders;

use App\Models\JoinRequest;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

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

        $requester = User::where('id', '!=', $user->id)->first();
        if (!$requester) {
            $requester = User::factory()->create();
        }

        JoinRequest::create([
            'project_id' => 1,
            'user_id'    => $requester->id,
            'status'     => 'pending',
        ]);
    }
}
