<?php

namespace App\Actions\Leave;

use App\Models\LeaveApplication;
use Illuminate\Support\Facades\Auth;

class GetLeave
{
    public function handle(): array
    {
        $user = Auth::user();

        $requests = $user->can('manage leave applications')
            ? LeaveApplication::with('user:id,name')
                ->orderByRaw("FIELD(status, 'pending', 'approved', 'rejected')")
                ->latest()
                ->get()
            : LeaveApplication::where('user_id', $user->id)
                ->latest()
                ->get();

        // ✅ Highlighted dates for calendar (pending or approved)
        $highlighted = [];

        foreach ($requests as $request) {
            if (in_array($request->status, ['pending', 'approved'])) {
                $highlighted[] = [
                    'start' => $request->start_date,
                    'end' => $request->end_date,
                    'title' => ucfirst($request->status).' Leave',
                    'class' => $request->status,
                ];
            }
        }

        return [
            'leaveRequests' => $requests,
            'canManage' => $user->can('manage leave applications'),
            'highlightedDates' => $highlighted,
        ];
    }
}
