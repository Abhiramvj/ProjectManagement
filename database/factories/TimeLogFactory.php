<?php

namespace Database\Factories;

use App\Models\Project; // <-- IMPORTANT: Import the Project model
use App\Models\User;    // <-- IMPORTANT: Import the User model
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TimeLog>
 */
class TimeLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // This factory will now create all necessary dependencies automatically.
            'user_id' => User::factory(),
            'project_id' => Project::factory(),

            // Generate realistic data for the other fields.
            'work_date' => $this->faker->dateTimeThisMonth(),
            'hours_worked' => $this->faker->randomFloat(2, 1, 8), // e.g., 4.50 or 7.25
            'description' => $this->faker->sentence(),
        ];
    }
}
