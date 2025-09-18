<?php 
namespace App\Services;

use App\Models\Milestone;
use Carbon\Carbon;

class MilestoneService
{
    public function updateMilestonesForUser($user, $sessionId = null)
    {
        $completedCount = $user->projectSessions()->where('status', 'completed')->count();

        $milestones = Milestone::all(); // example: Rising Star (3), Mentor (5), etc.

        foreach ($milestones as $milestone) {
            // If user meets or exceeds the milestone
            if ($completedCount >= $milestone->required_sessions) {
                $exists = $user->milestones()
                               ->where('milestone_id', $milestone->id)
                               ->exists();

                if (!$exists) {
                    $user->milestones()->attach($milestone->id, [
                        'session_id'  => $sessionId,
                        'progress'    => $completedCount,
                        'unlocked_at' => Carbon::now(),
                    ]);
                } else {
                    // Optionally update progress
                    $user->milestones()->updateExistingPivot($milestone->id, [
                        'progress'    => $completedCount,
                        'updated_at'  => Carbon::now(),
                    ]);
                }
            }
        }
    }
}
