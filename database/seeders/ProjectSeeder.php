<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        // You can choose how many projects to seed
        Project::factory()->count(10)->create();
    }
}
