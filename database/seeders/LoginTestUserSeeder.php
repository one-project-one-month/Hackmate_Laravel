<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LoginTestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::firstOrNew([
            'email' => 'test@example.com',
        ]);

        $user->name = 'Test User';
        $user->password = Hash::make('password');
        $user->email_verified_at = now();
        $user->github_username = null;
        $user->github_id = null;
        $user->bio = null;

        $user->save();
    }
}
