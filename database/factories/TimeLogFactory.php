<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TimeLogFactory extends Factory
{
    public function definition(): array
    {
        $userIds = User::pluck('id')->toArray();
        $projectIds = Project::pluck('id')->toArray();

        return [
            'user_id' => ! empty($userIds) ? $this->faker->randomElement($userIds) : User::factory(),
            'project_id' => ! empty($projectIds) ? $this->faker->randomElement($projectIds) : Project::factory(),
            'work_date' => $this->faker->dateTimeThisMonth(),
            'hours_worked' => $this->faker->randomFloat(2, 1, 8),
            'description' => $this->faker->sentence(),
        ];
    }
}
