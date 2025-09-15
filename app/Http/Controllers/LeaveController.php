<?php

namespace App\Http\Controllers;

use App\Actions\Leave\GetLeaveRequestsAction;
use App\Actions\Leave\GetUserLeaveRequestsAction;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeaveController extends Controller
{
    protected GetLeaveRequestsAction $getLeaveRequestsAction;

    protected GetUserLeaveRequestsAction $getUserLeaveRequestsAction;

    public function __construct(GetLeaveRequestsAction $getLeaveRequestsAction, GetUserLeaveRequestsAction $getUserLeaveRequestsAction)
    {
        $this->getLeaveRequestsAction = $getLeaveRequestsAction;
        $this->getUserLeaveRequestsAction = $getUserLeaveRequestsAction;
    }

    public function manageRequests(Request $request)
    {
        $leaveRequests = $this->getLeaveRequestsAction->execute();

        return Inertia::render('Leave/manageRequests', [
            'leaveRequests' => $leaveRequests->toArray(),
            'canManage' => true,
        ]);
    }

    public function fullRequests(Request $request)
    {
        $leaveRequests = $this->getUserLeaveRequestsAction->execute($request);

        return Inertia::render('Leave/FullRequests', [
            'leaveRequests' => $leaveRequests,
            'filters' => $request->only(['status', 'leave_type']),
        ]);
    }
}
