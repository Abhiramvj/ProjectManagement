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
    $pmUserIds = \App\Models\User::pluck('id')->toArray();
    $teamIds = \App\Models\Team::pluck('id')->toArray();

    return [
        'name' => 'Project '.fake()->company(),
        'description' => fake()->paragraph(),
        'project_manager_id' => !empty($pmUserIds) ? fake()->randomElement($pmUserIds) : User::factory(),
        'team_id' => !empty($teamIds) ? fake()->randomElement($teamIds) : Team::factory(),
        'status' => fake()->randomElement(['pending', 'in-progress', 'on-hold']),
        'end_date' => fake()->dateTimeBetween('+1 month', '+6 months'),
        'total_hours_required' => fake()->numberBetween(100, 500),
    ];
}
}
