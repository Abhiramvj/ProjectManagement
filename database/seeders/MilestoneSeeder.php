<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Milestone;

class MilestoneSeeder extends Seeder
{
    public function run()
    {
        $milestones = [
            [
                'name' => 'Rising Star',
                'target' => 3,
                'description' => 'Awarded for making 3 sessions',
                'icon' => '/milestones/star.png', // path in public or storage
            ],
            [
                'name' => 'Mentor Level 1',
                'target' => 5,
                'description' => 'Awarded for mentoring 5 sessions',
                'icon' => '/milestones/graduation-cap.png',
            ],
            [
                'name' => 'Knowledge Champion',
                'target' => 10,
                'description' => 'Awarded for sharing 10 sessions',
                'icon' => '/milestones/running-man.png',
            ],
            [
                'name' => 'Master Speaker',
                'target' => 20,
                'description' => 'Awarded for reaching mastery with 20 sessions',
                'icon' => '/milestones/crown.png',
            ],
        ];

        foreach ($milestones as $milestone) {
            Milestone::updateOrCreate(
                ['name' => $milestone['name']],
                $milestone
            );
        }
    }
}
