<?php

namespace App\Http\Controllers;

use App\Models\LeaveApplication;
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

        // Confirm user is authorized to manage leave applications (e.g. middleware or gate)
        abort_unless($user->can('manage leave applications'), 403);

        $leaveRequests = LeaveApplication::with('user:id,name,email')
            ->orderByRaw("CASE status
            WHEN 'pending' THEN 1
            WHEN 'approved' THEN 2
            WHEN 'rejected' THEN 3
            ELSE 4
        END")
            ->latest()
            ->paginate(15);

        return Inertia::render('Leave/LeaveLogs', [
            'leaveRequests' => $leaveRequests,
            'canManage' => true,
            'highlightId' => $request->query('highlight'),
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
