<?php

namespace App\Actions\LeaveCalendar;

use App\Helpers\ColorHelper;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GetLeaveCalendarDataAction
{
    public function execute(Request $request): array
    {
        $filters = $this->getValidatedFilters($request);
        $startDate = Carbon::parse($filters['start_date']);
        $endDate = Carbon::parse($filters['end_date']);
        $user = Auth::user();

        $usersQuery = User::query();
        $ledTeamIds = collect();

        // Determine which users the current user can see
        if ($user->hasAnyRole(['admin', 'hr'])) {
            $usersQuery->whereHas('roles', fn ($q) => $q->whereNotIn('name', ['super-admin']));
        } elseif ($user->hasRole('team-lead')) {
            $ledTeamIds = $user->ledTeams()->pluck('id');
            if ($ledTeamIds->isEmpty()) {
                $usersQuery->whereRaw('1 = 0');
            } else {
                $memberIds = DB::table('team_user')
                    ->whereIn('team_id', $ledTeamIds)
                    ->pluck('user_id')
                    ->unique();
                $usersQuery->whereIn('id', $memberIds);
            }
        } else {
            $teamIds = $user->teams()->pluck('teams.id');
            $memberIds = DB::table('team_user')
                ->whereIn('team_id', $teamIds)
                ->pluck('user_id')
                ->unique();
            $usersQuery->whereIn('id', $memberIds);
        }

        // Load leaves and teams
        $usersQuery->with([
            'leaveApplications' => function ($query) use ($startDate, $endDate) {
                $query->approved()->overlapsWith($startDate, $endDate)
                    ->select('id', 'user_id', 'start_date', 'end_date', 'leave_type', 'day_type', 'start_half_session', 'end_half_session', 'leave_days');
            },
            'teams:id,name',
        ])
            ->select('id', 'name')
            ->orderBy('name');

        // Apply filters
        $this->applyFilters($usersQuery, $filters, $user, $ledTeamIds);

        $paginatedUsers = $usersQuery->paginate(50)->withQueryString();

        // Generate date range
        $dateRange = collect(CarbonPeriod::create($startDate, $endDate))
            ->map(fn ($date) => $date->format('Y-m-d'));

        // Format users for calendar
        $calendarData = $paginatedUsers->through(fn ($user) => $this->formatSingleUserData($user, $dateRange));

        // Teams for filter dropdown
        $teamsForFilter = collect();
        if ($user->hasAnyRole(['admin', 'hr'])) {
            $teamsForFilter = Team::orderBy('name')->get(['id', 'name']);
        } elseif ($user->hasRole('team-lead')) {
            $teamsForFilter = $user->ledTeams()->select('id', 'name')->orderBy('name')->get();
        } else {
            $teamsForFilter = $user->teams()->select('id', 'name')->orderBy('name')->get();
        }

        return [
            'calendarData' => $calendarData,
            'dateRange' => $dateRange,
            'teams' => $teamsForFilter,
            'filters' => $filters,
            'authUserRole' => $user->roles->pluck('name')->first(),
        ];
    }

    private function getValidatedFilters(Request $request): array
    {
        $defaults = [
            'start_date' => now()->startOfMonth()->format('Y-m-d'),
            'end_date' => now()->endOfMonth()->format('Y-m-d'),
            'absent_only' => false,
            'search' => null,
            'team_id' => null,
        ];

        $data = array_merge($defaults, $request->all());

        return Validator::make($data, [
            'search' => 'nullable|string|max:255',
            'team_id' => 'nullable|integer|exists:teams,id',
            'start_date' => 'required|date|before_or_equal:end_date',
            'end_date' => 'required|date',
            'absent_only' => 'required|boolean',
        ])->validate();
    }

    private function applyFilters($query, array $filters, User $user, Collection $ledTeamIds): void
    {
        if (! empty($filters['search'])) {
            $query->where('name', 'like', "%{$filters['search']}%");
        }

        if (! empty($filters['team_id'])) {
            if ($user->hasRole('team-lead') && ! $ledTeamIds->contains($filters['team_id'])) {
                abort(403);
            }

            $query->whereHas('teams', function ($teamQuery) use ($filters) {
                $teamQuery->where('teams.id', $filters['team_id']);
            });
        }

        if ($filters['absent_only']) {
            $query->whereHas('leaveApplications', function ($q) use ($filters) {
                $q->approved()->overlapsWith(
                    \Carbon\Carbon::parse($filters['start_date']),
                    \Carbon\Carbon::parse($filters['end_date'])
                );
            });
        }

    }

    private function formatSingleUserData(User $user, Collection $dateRange): array
    {
        $currentUser = Auth::user();
        $today = now()->startOfDay();
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

                if ($currentUser->hasAnyRole(['admin', 'hr', 'team-lead'])) {
                    $details = [
                        'code' => strtoupper(substr($onLeave->leave_type, 0, 1)),
                        'color' => ColorHelper::getLeaveColor($onLeave->leave_type),
                        'leave_type' => ucfirst($onLeave->leave_type),
                        'day_type' => $onLeave->day_type,
                        'start_half_session' => $onLeave->start_half_session,
                        'end_half_session' => $onLeave->end_half_session,
                        'leave_days' => $onLeave->leave_days,
                    ];
                }
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
    }
}
