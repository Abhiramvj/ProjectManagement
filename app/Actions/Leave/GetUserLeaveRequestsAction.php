<?php

namespace App\Actions\Leave;

use App\Models\LeaveApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GetUserLeaveRequestsAction
{
    public function execute(Request $request)
    {
        $user = Auth::user();

        // Base query for user's leave requests
        $query = LeaveApplication::with('user:id,name')
            ->where('user_id', $user->id)
            ->select([
                'id',
                'user_id',
                'start_date',
                'start_half_session',
                'end_date',
                'end_half_session',
                'reason',
                'leave_type',
                'status',
                'created_at',
                'rejection_reason',
                'leave_days',
                'supporting_document_path',
            ])
            ->orderBy('start_date', 'desc');

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('leave_type')) {
            $query->where('leave_type', $request->input('leave_type'));
        }

        $leaveRequests = $query->paginate(15)->withQueryString();

        // Append supporting document URLs
        $leaveRequests->getCollection()->transform(function ($leave) {
            $leave->supporting_document = $leave->supporting_document_path
                ? asset('storage/'.$leave->supporting_document_path)
                : null;

            return $leave;
        });

        return $leaveRequests;
    }
}
