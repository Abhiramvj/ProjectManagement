<?php
namespace App\Services;

use App\Models\Badge;
use Carbon\Carbon;

class BadgeService
{
    public function awardBadgesToUser($user, $sessionId = null)
    {
        $completedCount = $user->projectSessions()->where('status', 'completed')->count();

        if ($completedCount >= 5) {
            $badgeName = 'Gold';
        } elseif ($completedCount >= 3) {
            $badgeName = 'Silver';
        } elseif ($completedCount >= 1) {
            $badgeName = 'Bronze';
        } else {
            return; // No badge to assign
        }

        $badge = Badge::where('name', $badgeName)->first();
        if ($badge && !$user->badges()->where('badge_id', $badge->id)->exists()) {
            $user->badges()->attach($badge->id, ['unlocked_at' => Carbon::now(),
        'session_id' => $sessionId]);
        }
    }
}
