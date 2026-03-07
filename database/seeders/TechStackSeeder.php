<?php

namespace Database\Seeders;

use App\Models\TechStack;
use Illuminate\Database\Seeder;

class TechStackSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stacks = [
            ['name' => 'PHP', 'category' => 'language'],
            ['name' => 'JavaScript', 'category' => 'language'],
            ['name' => 'Laravel', 'category' => 'framework'],
            ['name' => 'React', 'category' => 'framework'],
            ['name' => 'Nest', 'category' => 'framework'],
            ['name' => 'PostgreSQL', 'category' => 'database'],
            ['name' => 'Docker', 'category' => 'tool'],
            ['name' => 'Git', 'category' => 'tool'],
        ];

        foreach ($stacks as $stack) {
            TechStack::updateOrCreate(['name' => $stack['name']], $stack);
        }
    }
}
