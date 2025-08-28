<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    public function run()
    {
        // Fetch all users with role "team-lead"
        $teamLeads = User::role('team-lead')->get();

        if ($teamLeads->isEmpty()) {
            $this->command->info('No team leads found. Please seed team leads first.');

            return;
        }

        // Create 10 teams, each assigned to a distinct team lead
        foreach ($teamLeads->take(10) as $lead) {
            Team::factory()->create([
                'name' => 'Team '.$lead->name,
                'team_lead_id' => $lead->id,
            ]);
        }

        // If less than 10 team leads, fill rest randomly
        $remaining = 10 - $teamLeads->count();
        if ($remaining > 0) {
            $allLeads = $teamLeads->isNotEmpty() ? $teamLeads : User::all();
            for ($i = 0; $i < $remaining; $i++) {
                $lead = $allLeads->random();
                Team::factory()->create([
                    'name' => 'Team '.$lead->name.' #'.($i + 1),
                    'team_lead_id' => $lead->id,
                ]);
            }
        }
    }
}
