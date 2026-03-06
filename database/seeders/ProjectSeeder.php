<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project; 
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Get the first user (the one we saw earlier in your psql output)
        $user = User::first();

        // 2. Create specific projects manually
        Project::create([
            'title' => 'E-commerce Platform',
            'description' => 'A full-stack online store built with Laravel and Vue.',
            'type' => 'Web Application',
            'created_by_user_id' => $user->id ?? null,
            'github_repo' => 'https://github.com/user/ecommerce',
            'is_active' => true,
        ]);

        Project::create([
            'title' => 'Mobile Fitness Tracker',
            'description' => 'Cross-platform app for tracking daily steps.',
            'type' => 'Mobile App',
            'created_by_user_id' => $user->id ?? null,
            'github_repo' => null,
            'is_active' => true,
        ]);

        // 3. (Optional) Create random projects if you have a Factory set up
        //Project::factory()->count(10)->create();
    }
}