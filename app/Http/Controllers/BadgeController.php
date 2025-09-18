<?php

namespace App\Http\Controllers;

use App\Models\Badge;
use App\Models\User;
use App\Models\ProjectSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BadgeController extends Controller
{
    public function index(Request $request)
    {
        // Badge stats: count the awarded badges by type
        $bronzeCount = DB::table('badge_user')
            ->join('badges', 'badge_user.badge_id', '=', 'badges.id')
            ->where('badges.name', 'Bronze')
            ->count();

        $silverCount = DB::table('badge_user')
            ->join('badges', 'badge_user.badge_id', '=', 'badges.id')
            ->where('badges.name', 'Silver')
            ->count();

        $goldCount = DB::table('badge_user')
            ->join('badges', 'badge_user.badge_id', '=', 'badges.id')
            ->where('badges.name', 'Gold')
            ->count();

        $totalBadges = DB::table('badge_user')->count();

        $badgesThisMonth = DB::table('badge_user')
            ->whereMonth('unlocked_at', now()->month)
            ->whereYear('unlocked_at', now()->year)
            ->count();

        // Employee badge overview
        $users = User::withCount([
                'badges',
                'projectSessions as sessions_count' => function ($q) {
                    $q->where('status', 'completed');
                }
            ])
            ->with('badges')
            ->get();

        // Recent badge award activity (e.g. latest 5)
        $recentActivity = DB::table('badge_user')
            ->orderByDesc('unlocked_at')
            ->take(5)
            ->get()
            ->map(function ($activity) {
                $user = User::find($activity->user_id);
                $badge = Badge::find($activity->badge_id);
                $session = ProjectSession::find($activity->session_id);
                $topic = $session ? $session->topic : null;

                return [
                    'user_name' => $user->name,
                    'badge_name' => $badge->name,
                    'badge_icon' => $badge->icon,
                    'unlocked_at' => $activity->unlocked_at,
                    'topic' => $topic ?? 'N/A',
                ];
            });

        return Inertia::render('Sessions/Badges', [
            'stats' => [
                'total' => $totalBadges,
                'bronze' => $bronzeCount,
                'silver' => $silverCount,
                'gold' => $goldCount,
                'this_month' => $badgesThisMonth,
            ],
            'users' => $users,
            'recentActivity' => $recentActivity,
        ]);
    }

    public function search(Request $request)
{
    $term = $request->input('term', '');
    $users = User::where('name', 'like', "%{$term}%")
        ->withCount(['badges', 'projectSessions'])
        ->with('badges')
        ->paginate(15);

    return Inertia::render('Sessions/Badges', [
        'users' => $users,
        'searchTerm' => $term,
    ]);
}

}



