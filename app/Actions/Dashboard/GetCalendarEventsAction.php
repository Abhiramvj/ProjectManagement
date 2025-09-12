<?php

namespace App\Actions\Dashboard;

use App\Helpers\ColorHelper;
use App\Models\CalendarNote;
use App\Models\Holiday;
use App\Models\LeaveApplication;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class GetCalendarEventsAction
{
    public function execute(int $userId)
    {
        $leaveEvents = LeaveApplication::where('user_id', $userId)
            ->where('status', 'approved')
            ->get()
            ->map(function ($leave) {
                return [
                    'id' => 'leave_'.$leave->id,
                    'title' => ucfirst($leave->leave_type).' Leave',
                    'start' => $leave->start_date,
                    'end' => Carbon::parse($leave->end_date)->addDay()->toDateString(),
                    'allDay' => true,
                    'backgroundColor' => ColorHelper::getLeaveColor($leave->leave_type),
                    'borderColor' => ColorHelper::getLeaveColor($leave->leave_type),
                    'textColor' => '#ffffff',
                    'extendedProps' => [
                        'type' => 'leave',
                        'leave_type' => $leave->leave_type,
                        'status' => $leave->status,
                        'day_type' => $leave->day_type ?? 'full_day',
                    ],
                ];
            });

        $noteEvents = CalendarNote::where('user_id', $userId)
            ->get()
            ->map(function ($note) {
                return [
                    'id' => 'note_'.$note->id,
                    'title' => $note->note,
                    'start' => $note->date,
                    'allDay' => true,
                    'backgroundColor' => '#FBBF24',
                    'borderColor' => '#F59E0B',
                    'textColor' => '#000000',
                    'extendedProps' => [
                        'type' => 'note',
                        'note_id' => $note->id,
                    ],
                ];
            });

        $holidayEvents = Holiday::all()->map(function ($holiday) {
            return [
                'id' => 'holiday_'.$holiday->id,
                'title' => $holiday->name,
                'start' => $holiday->date->toDateString(),
                'allDay' => true,
                'backgroundColor' => '#10B981',
                'borderColor' => '#059669',
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'type' => 'holiday',
                ],
            ];
        });

        return (new Collection($leaveEvents))
            ->merge($noteEvents)
            ->merge($holidayEvents);
    }
}
