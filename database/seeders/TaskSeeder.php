<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run()
    {
        $projects = Project::all();

        if ($projects->isEmpty()) {
            return; // No projects, nothing to seed tasks for
        }

        $users = User::all();

        if ($users->isEmpty()) {
            return; // No users to assign tasks to
        }

        // Define some sample statuses to assign randomly
        $statuses = ['todo', 'in_progress', 'completed'];

        for ($i = 1; $i <= 200; $i++) {
            // Pick a random project and user
            $project = $projects->random();
            $user = $users->random();

            Task::factory()->create([
                'name' => "Task #{$i}",
                'project_id' => $project->id,
                'assigned_to_id' => $user->id,
                'status' => $statuses[array_rand($statuses)],
                'due_date' => now()->addDays(rand(1, 60)),
            ]);
        }
    }
}
