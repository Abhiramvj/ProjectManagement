<?php

namespace App\Actions\Leave;

use App\Models\LeaveApplication;
use App\Models\Team;
use Illuminate\Support\Facades\Auth;

class GetLeaveRequestsAction
{
    public function execute()
    {
        $user = Auth::user();

        abort_unless($user->can('manage leave applications'), 403);

        $query = LeaveApplication::with('user:id,name,email');

        if ($user->hasRole('admin') || $user->hasRole('hr')) {
            // Admin and HR see all leave requests - no filter
        } elseif ($user->hasRole('team-lead')) {
            // Get teams where current user is team lead, along with their members
            $teams = Team::where('team_lead_id', $user->id)->with('members')->get();

            // Extract all member IDs across these teams
            $memberIds = $teams->flatMap(function ($team) {
                return $team->members->pluck('id');
            })->unique();

            // Filter leave requests to only these members
            $query->whereIn('user_id', $memberIds);
        } else {
            // Other roles get unauthorized
            abort(403, 'Unauthorized');
        }

        $leaveRequests = $query->orderByRaw("CASE status
        WHEN 'pending' THEN 1
        WHEN 'approved' THEN 2
        WHEN 'rejected' THEN 3
        ELSE 4
    END")
            ->latest()
            ->paginate(15);

        return $leaveRequests;

    }
}
