<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\CalendarNote;
use App\Models\Holiday;
use App\Models\LeaveApplication;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardDataFetcher
{
    /**
     * Get attendance data for dashboard.
     */
    public function getAttendanceData(User $user): array
    {
        $today = now()->toDateString();

        // Eager load leave applications for today
        $absentTodayUsers = User::with(['leaveApplications' => function ($q) use ($today) {
            $q->where('status', 'approved')
                ->where('start_date', '<=', $today)
                ->where('end_date', '>=', $today);
        }])->whereHas('leaveApplications', function ($query) use ($today) {
            $query->where('status', 'approved')
                ->where('start_date', '<=', $today)
                ->where('end_date', '>=', $today);
        })->get();

        $totalEmployees = User::count();
        $absentCount = $absentTodayUsers->count();

        return [
            'total' => $totalEmployees,
            'present' => $totalEmployees - $absentCount,
            'absent' => $absentCount,
            'absent_list' => $absentTodayUsers->map(function ($user) {
                $leave = $user->leaveApplications->first();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'designation' => $user->designation,
                    'avatar_url' => $user->avatar_url,
                    'day_type' => $leave?->day_type ?? 'full',
                    'half_session' => $leave?->half_session,
                ];
            }),
        ];
    }

    /**
     * Get all calendar events (leaves, notes, holidays).
     */
    public function getCalendarEvents(User $user): Collection
    {
        $leaveEvents = LeaveApplication::where('user_id', $user->id)
            ->where('status', 'approved')
            ->get()
            ->map(function ($leave) {
                return [
                    'id' => 'leave_'.$leave->id,
                    'title' => ucfirst($leave->leave_type).' Leave',
                    'start' => $leave->start_date,
                    'end' => Carbon::parse($leave->end_date)->addDay()->toDateString(),
                    'allDay' => true,
                    'backgroundColor' => $this->getLeaveColor($leave->leave_type),
                    'borderColor' => $this->getLeaveColor($leave->leave_type),
                    'textColor' => '#ffffff',
                    'extendedProps' => [
                        'type' => 'leave',
                        'leave_type' => $leave->leave_type,
                        'status' => $leave->status,
                        'day_type' => $leave->day_type ?? 'full_day',
                    ],
                ];
            });

        $noteEvents = CalendarNote::where('user_id', $user->id)
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

        $holidayEvents = Holiday::all()
            ->map(function ($holiday) {
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

    /**
     * Get latest 5 announcements.
     */
    public function getAnnouncements(): Collection
    {
        return Announcement::with('user:id,name,avatar_url')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($announcement) {
                return [
                    'id' => $announcement->id,
                    'title' => $announcement->title,
                    'content' => $announcement->content,
                    'author' => $announcement->user,
                    'created_at_formatted' => $announcement->created_at->format('M d, Y'),
                ];
            });
    }

    /**
     * Get time-based greeting data.
     */
    public function getGreetingData(): array
    {
        $hour = now()->hour;

        if ($hour >= 12 && $hour < 17) {
            return ['message' => 'Afternoon', 'icon' => '☀️'];
        } elseif ($hour >= 17) {
            return ['message' => 'Evening', 'icon' => '🌙'];
        }

        return ['message' => 'Morning', 'icon' => '🌤️'];
    }

    /**
     * Get color config for leave types.
     */
    private function getLeaveColor($leaveType): string
    {
        return config("leave_colors.{$leaveType}", '#6B7280');  // Fallback gray color
    }
}
