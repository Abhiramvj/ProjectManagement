<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Team;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection as EloquentCollection; // <-- Add or confirm this use statement
use Illuminate\Support\Collection; // For the date range collection
use Illuminate\Support\Facades\Validator;

class LeaveCalendarController extends Controller
{
    public function index(Request $request)
    {
        // 1. Get validated filters
        $filters = $this->getValidatedFilters($request);
        $startDate = Carbon::parse($filters['start_date']);
        $endDate = Carbon::parse($filters['end_date']);

        // 2. Build users query
        $usersQuery = User::query()
            ->with([
                'leaveApplications' => function ($query) use ($startDate, $endDate) {
                    $query->approved()
                        ->overlapsWith($startDate, $endDate)
                        ->select('id', 'user_id', 'start_date', 'end_date', 'leave_type', 'day_type');
                },
                'teams:id,name'
            ])
            ->select('id', 'name')
            ->orderBy('name');

        $this->applyFilters($usersQuery, $filters);
        $users = $usersQuery->get();

        // 3. Generate date range
        $dateRange = collect(CarbonPeriod::create($startDate, $endDate))->map(fn ($date) => $date->format('Y-m-d'));

        // 4. Process calendar data - THIS LINE WILL NOW WORK
        $calendarData = $this->formatCalendarData($users, $dateRange);

        // 5. Apply "show absent only" filter
        if ($filters['show_absent_only']) {
            $calendarData = $calendarData->filter(fn ($user) => $user['has_absence'])->values();
        }

        // 6. Render the Inertia component
        return Inertia::render('Leave/Calendar', [
            'calendarData' => $calendarData,
            'dateRange' => $dateRange,
            'teams' => Team::orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
        ]);
    }

    private function getValidatedFilters(Request $request): array
    {
        $defaults = [
            'start_date' => now()->startOfMonth()->format('Y-m-d'),
            'end_date' => now()->endOfMonth()->format('Y-m-d'),
            'show_absent_only' => false,
            'employee_name' => null,
            'team_id' => null,
        ];

        $data = array_merge($defaults, $request->all());

        return Validator::make($data, [
            'employee_name' => 'nullable|string|max:255',
            'team_id' => 'nullable|integer|exists:teams,id',
            'start_date' => 'required|date|before_or_equal:end_date',
            'end_date' => 'required|date',
            'show_absent_only' => 'required|boolean',
        ])->validate();
    }

    private function applyFilters($query, array $filters): void
    {
        if (!empty($filters['employee_name'])) {
            $query->where('name', 'like', "%{$filters['employee_name']}%");
        }

        if (!empty($filters['team_id'])) {
            $query->whereHas('teams', function($teamQuery) use ($filters) {
                $teamQuery->where('teams.id', $filters['team_id']);
            });
        }
    }

    // --- THIS IS THE FIX ---
    // The type hint for $users has been changed to the correct EloquentCollection class.
    private function formatCalendarData(EloquentCollection $users, Collection $dateRange): Collection
    {
        $today = now()->startOfDay();

        return $users->map(function ($user) use ($dateRange, $today) {
            $dailyStatuses = [];
            $hasAbsence = false;

            foreach ($dateRange as $dateString) {
                $date = Carbon::parse($dateString);
                $status = 'Working';
                $details = null;

                $onLeave = $user->leaveApplications->first(
                    fn ($leave) => $date->betweenIncluded($leave->start_date, $leave->end_date)
                );

                if ($onLeave) {
                    $hasAbsence = true;
                    $status = 'Leave';
                    $details = [
                        'code' => strtoupper(substr($onLeave->leave_type, 0, 1)),
                        'color' => $this->getLeaveTypeColor($onLeave->leave_type),
                        'leave_type' => ucfirst($onLeave->leave_type),
                        'day_type' => $onLeave->day_type,
                    ];
                } elseif ($date->isWeekend()) {
                    $status = 'Weekend';
                } elseif ($date->isAfter($today)) {
                    $status = 'Future';
                }

                $dailyStatuses[$dateString] = ['status' => $status, 'details' => $details];
            }

            return [
                'id' => $user->id,
                'name' => $user->name,
                'teams' => $user->teams->pluck('name')->join(', '),
                'daily_statuses' => $dailyStatuses,
                'has_absence' => $hasAbsence,
            ];
        });
    }

    private function getLeaveTypeColor(string $leaveType): string
    {
        $colors = [
            'sick' => '#ff9800', 'annual' => '#EF5350', 'personal' => '#9C27B0',
            'emergency' => '#F44336', 'maternity' => '#E91E63', 'paternity' => '#3F51B5',
        ];
        return $colors[$leaveType] ?? '#607D8B';
    }
}
