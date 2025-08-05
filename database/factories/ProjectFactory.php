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
            'name' => $this->faker->company,
            'description' => $this->faker->paragraph,
            'status' => 'in-progress',
            'end_date' => now()->addMonths(3),
            'total_hours_required' => $this->faker->numberBetween(100, 200),
            'project_manager_id' => User::factory(),
            'team_id' => Team::factory(),
        ];
    }
}
