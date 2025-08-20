<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class LeaveCalendarController extends Controller
{
    public function index(Request $request)
    {
        // 1. Get validated filters
        $filters = $this->getValidatedFilters($request);
        $startDate = Carbon::parse($filters['start_date']);
        $endDate = Carbon::parse($filters['end_date']);

        // 2. Build the base user query with necessary relationships
        $usersQuery = User::query()
            ->with([
                'leaveApplications' => function ($query) use ($startDate, $endDate) {
                    $query->approved()
                        ->overlapsWith($startDate, $endDate)
                        ->select('id', 'user_id', 'start_date', 'end_date', 'leave_type', 'day_type');
                },
                'teams:id,name',
            ])
            ->select('id', 'name')
            ->orderBy('name');

        // 3. Apply filters to the query
        $this->applyFilters($usersQuery, $filters, $startDate, $endDate);

        // 4. Execute the paginated query
        // This is the key performance fix.
        $paginatedUsers = $usersQuery->paginate(25)->withQueryString();

        // 5. Generate the date range for the calendar header
        $dateRange = collect(CarbonPeriod::create($startDate, $endDate))->map(fn ($date) => $date->format('Y-m-d'));
        $today = now()->startOfDay();

        // 6. Transform the paginated results for the calendar view
        // The `through()` method applies a callback to each item on the current page.
        $calendarData = $paginatedUsers->through(function ($user) use ($dateRange, $today) {
            $dailyStatuses = [];
            $hasAbsence = false; // We can still track this for potential UI use

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


        // 7. Render the Inertia component with paginated data
        return Inertia::render('Leave/Calendar', [
            'calendarData' => $calendarData, // Pass the entire paginated and transformed object
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

        // Ensure show_absent_only is correctly cast from '1'/'0' to boolean
        $requestData = $request->all();
        if (isset($requestData['show_absent_only'])) {
            $requestData['show_absent_only'] = filter_var($requestData['show_absent_only'], FILTER_VALIDATE_BOOLEAN);
        }

        $data = array_merge($defaults, $requestData);

        return Validator::make($data, [
            'employee_name' => 'nullable|string|max:255',
            'team_id' => 'nullable|integer|exists:teams,id',
            'start_date' => 'required|date|before_or_equal:end_date',
            'end_date' => 'required|date',
            'show_absent_only' => 'required|boolean',
        ])->validate();
    }

    private function applyFilters($query, array $filters, Carbon $startDate, Carbon $endDate): void
    {
        // Name and Team filters
        $query->when($filters['employee_name'], function ($query, $name) {
            $query->where('name', 'like', "%{$name}%");
        });

        $query->when($filters['team_id'], function ($query, $teamId) {
            $query->whereHas('teams', fn ($q) => $q->where('teams.id', $teamId));
        });

        // Efficiently apply "show absent only" at the database level
        $query->when($filters['show_absent_only'], function ($query) use ($startDate, $endDate) {
            $query->whereHas('leaveApplications', function ($q) use ($startDate, $endDate) {
                $q->approved()->overlapsWith($startDate, $endDate);
            });
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
