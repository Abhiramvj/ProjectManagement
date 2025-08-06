<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => 'Project ' . fake()->company(),
            'description' => fake()->paragraph(),

            // This line ensures a user is created for the project manager
            'project_manager_id' => User::factory(),

            // This line ensures a team is created for the project
            'team_id' => Team::factory(),

            'status' => fake()->randomElement(['pending', 'in-progress', 'on-hold']),
            'end_date' => fake()->dateTimeBetween('+1 month', '+6 months'),
            'total_hours_required' => fake()->numberBetween(100, 500),
        ];
    }
}
