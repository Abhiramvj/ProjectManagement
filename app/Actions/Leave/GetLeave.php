<?php

namespace App\Actions\Leave;

use App\Models\LeaveApplication;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

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
            if (in_array($request->status, ['approved', 'pending'])) {
                $start = Carbon::parse($request->start_date);
                $end = Carbon::parse($request->end_date);

                while ($start->lte($end)) {
                    $highlighted[] = $start->toDateString(); // 'YYYY-MM-DD'
                    $start->addDay();
                }
            }
        }

        return [
            'leaveRequests' => $requests,
            'canManage' => $user->can('manage leave applications'),
            'highlightedDates' => array_values(array_unique($highlighted)), // ✅ remove duplicates
        ];
    }
}
    