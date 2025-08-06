<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst($this->faker->words(3, true)),

            'project_id' => Project::factory(),

            'assigned_to_id' => User::factory(),

            'status' => $this->faker->randomElement(['todo', 'in_progress', 'completed']),
            'due_date' => $this->faker->dateTimeBetween('now', '+2 months'),
        ];
    }
}
