<?php

namespace Database\Seeders;

use App\Models\LeaveApplication;
use App\Models\User;
use Illuminate\Database\Seeder;

class LeaveApplicationSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        LeaveApplication::factory()->count(300)->make()->each(function ($leaveApplication) use ($users) {
            $leaveApplication->user_id = $users->random()->id;
            $leaveApplication->save();
        });

        foreach ($users as $user) {
            // Approved leave in the past
            LeaveApplication::factory()->create([
                'user_id' => $user->id,
                'start_date' => now()->subDays(rand(10, 20)),
                'end_date' => now()->subDays(rand(5, 9)),
                'status' => 'approved',
                'leave_type' => 'annual',
                'day_type' => 'full_day',
            ]);

            // Pending leave in the future
            LeaveApplication::factory()->create([
                'user_id' => $user->id,
                'start_date' => now()->addDays(rand(5, 10)),
                'end_date' => now()->addDays(rand(11, 15)),
                'status' => 'pending',
                'leave_type' => 'annual',
                'day_type' => 'full_day',
            ]);

            // Approved half-day sick leave
            LeaveApplication::factory()->create([
                'user_id' => $user->id,
                'start_date' => now()->subDays(rand(1, 3)),
                'end_date' => now()->subDays(rand(1, 3)),
                'status' => 'approved',
                'leave_type' => 'sick',
                'day_type' => 'half_day',
                'start_half_session' => 'morning',
                'leave_days' => 0.5,
            ]);
        }
    }
}
