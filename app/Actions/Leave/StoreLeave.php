<?php

namespace App\Actions\Leave;

use App\Models\LeaveApplication;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class StoreLeave
{
    public function handle(array $data): LeaveApplication
    {
        $user = Auth::user();

        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);
        $requestedDays = $start->diffInDays($end) + 1;

        if ($user->leave_balance < $requestedDays) {
            throw new \Exception('You do not have enough leave balance for this request.');
        }

        return LeaveApplication::create([
            'user_id' => $user->id,
            'start_date' => $start,
            'end_date' => $end,
            'reason' => $data['reason'],
            'leave_type' => $data['leave_type'],
            'status' => 'pending',
        ]);
    }
}
