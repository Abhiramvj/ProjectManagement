<?php

namespace App\Http\Controllers;

use App\Models\LeaveApplication;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LeaveController extends Controller
{
    // ... your other existing controller methods like create(), store(), update(), etc. ...

    /**
     * Display a list of all relevant leave applications for the user.
     *
     * This method determines if the user is a manager or a regular employee.
     * Managers can see all leave requests, while employees can only see their own.
     * The data is paginated for performance and passed to the Inertia front-end.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @return \Inertia\Response
     */
    public function showLogs(Request $request)
    {
        $user = Auth::user();

        // Confirm user has permission to manage leave applications
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

        // Order by status priority and latest created
        $leaveRequests = $query->orderByRaw("CASE status
        WHEN 'pending' THEN 1
        WHEN 'approved' THEN 2
        WHEN 'rejected' THEN 3
        ELSE 4
    END")
            ->latest()
            ->paginate(15);

        // Render Inertia page with leave requests and permission flag
        return Inertia::render('Leave/LeaveLogs', [
            'leaveRequests' => $leaveRequests,
            'canManage' => true,
        ]);
    }

    public function fullRequests(Request $request)
    {
        $user = auth()->user();
        // Base query for user's leave requests (modify if admin can see all)
        $query = LeaveApplication::with('user:id,name')
            ->where('user_id', $user->id)
            ->select(['id', 'user_id', 'start_date', 'end_date', 'reason', 'leave_type', 'status', 'created_at', 'rejection_reason', 'leave_days'])
            ->orderBy('start_date', 'desc');

        // Optional: add filters if coming from query string
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('leave_type')) {
            $query->where('leave_type', $request->input('leave_type'));
        }

        $leaveRequests = $query->paginate(15)->withQueryString();

        return Inertia::render('Leave/FullRequests', [
            'leaveRequests' => $leaveRequests,
            'filters' => $request->only(['status', 'leave_type']),

        ]);
    }
    // public function update(Request $request, LeaveApplication $leaveApplication)
    // {
    //     // ...other logic...
    //     if ($request->status === 'rejected') {
    //         $request->validate(['rejection_reason' => 'required|string|max:500']);
    //         $leaveApplication->reject_reason = $request->rejection_reason;
    //     }
    //     // ...other updates...
    //     $leaveApplication->status = $request->status;
    //     $leaveApplication->save();
    //     // ...
    // }

}
