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
            ['name' => 'TypeScript', 'category' => 'language'],
            ['name' => 'Python', 'category' => 'language'],
            ['name' => 'Go', 'category' => 'language'],
            ['name' => 'Java', 'category' => 'language'],
            ['name' => 'Kotlin', 'category' => 'language'],
            ['name' => 'Swift', 'category' => 'language'],
            ['name' => 'Rust', 'category' => 'language'],
            ['name' => 'PHP', 'category' => 'language'],
            ['name' => 'JavaScript', 'category' => 'language'],

            ['name' => 'Laravel', 'category' => 'framework'],
            ['name' => 'React', 'category' => 'framework'],
            ['name' => 'Nest', 'category' => 'framework'],
            ['name' => 'Vue', 'category' => 'framework'],
            ['name' => 'Angular', 'category' => 'framework'],
            ['name' => 'Next.js', 'category' => 'framework'],
            ['name' => 'Express', 'category' => 'framework'],
            ['name' => 'Django', 'category' => 'framework'],
            ['name' => 'FastAPI', 'category' => 'framework'],
            ['name' => 'Spring Boot', 'category' => 'framework'],

            ['name' => 'PostgreSQL', 'category' => 'database'],
            ['name' => 'MySQL', 'category' => 'database'],
            ['name' => 'MongoDB', 'category' => 'database'],
            ['name' => 'Redis', 'category' => 'database'],
            ['name' => 'SQLite', 'category' => 'database'],

            ['name' => 'Docker', 'category' => 'tool'],
            ['name' => 'Kubernetes', 'category' => 'tool'],
            ['name' => 'Git', 'category' => 'tool'],
            ['name' => 'GitHub Actions', 'category' => 'tool'],
            ['name' => 'Terraform', 'category' => 'tool'],
            ['name' => 'AWS', 'category' => 'cloud'],
        ];

        foreach ($stacks as $stack) {
            TechStack::updateOrCreate(['name' => $stack['name']], $stack);
        }
    }
}
