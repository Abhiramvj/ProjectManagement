<?php

namespace App\Actions\Dashboard;

use App\Models\User;

class GetAttendanceDataAction
{
    public function execute()
    {
        $today = now()->toDateString();
        $totalEmployees = User::count();

        $absentTodayUsers = User::whereHas('leaveApplications', function ($query) use ($today) {
            $query->where('status', 'approved')
                ->where('start_date', '<=', $today)
                ->where('end_date', '>=', $today);
        })->get();

        return [
            'total' => $totalEmployees,
            'present' => $totalEmployees - $absentTodayUsers->count(),
            'absent' => $absentTodayUsers->count(),
            'absent_list' => $absentTodayUsers->map(function ($user) use ($today) {
                $leave = $user->leaveApplications()
                    ->where('status', 'approved')
                    ->where('start_date', '<=', $today)
                    ->where('end_date', '>=', $today)
                    ->first();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'designation' => $user->designation,
                    'avatar_url' => $user->avatar_url,
                    'day_type' => $leave?->day_type,
                    'start_half_session' => $leave?->start_half_session,
                    'end_half_session' => $leave?->end_half_session,
                    'leave_days' => $leave?->leave_days,
                ];
            }),
        ];
    }
}
