<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\TimeLog;
use Illuminate\Database\Seeder;

class TimeLogSeeder extends Seeder
{
    public function run(): void
    {
        // Loop through each project
        Project::all()->each(function ($project) {

            // Get user IDs assigned to this project
            $userIds = $project->members()->pluck('users.id')->toArray();

            if (empty($userIds)) {
                // If no users assigned, skip this project
                return;
            }

            // For each project, create 5 time logs with random assigned users
            for ($i = 0; $i < 500; $i++) {

                TimeLog::factory()->create([
                    'project_id' => $project->id,
                    'user_id' => fake()->randomElement($userIds),
                ]);
            }
        });
    }
}
