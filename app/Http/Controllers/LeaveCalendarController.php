<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class LeaveCalendarController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->getValidatedFilters($request);
        $startDate = Carbon::parse($filters['start_date']);
        $endDate = Carbon::parse($filters['end_date']);
        $user = Auth::user();

        $usersQuery = User::query();
        $ledTeamIds = collect();

        if ($user->hasAnyRole(['admin', 'hr'])) {
            // Admin and HR can see all users except super-admin
            $usersQuery->whereHas('roles', fn ($q) => $q->whereNotIn('name', ['super-admin']));
        } elseif ($user->hasRole('team-lead')) {
            // Team-lead sees users of teams they lead
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
            // Regular Employee sees all users in the same teams they belong to
            $teamIds = $user->teams()->pluck('teams.id');
            $memberIds = DB::table('team_user')
                ->whereIn('team_id', $teamIds)
                ->pluck('user_id')
                ->unique();
            $usersQuery->whereIn('id', $memberIds);
        }

        $usersQuery->with([
            'leaveApplications' => function ($query) use ($startDate, $endDate) {
                $query->approved()->overlapsWith($startDate, $endDate)
                    ->select('id', 'user_id', 'start_date', 'end_date', 'leave_type', 'day_type');
            },
            'teams:id,name',
        ])
            ->select('id', 'name')
            ->orderBy('name');

        $this->applyFilters($usersQuery, $filters, $user, $ledTeamIds);

        $paginatedUsers = $usersQuery->paginate(50)->withQueryString();

        $dateRange = collect(CarbonPeriod::create($startDate, $endDate))
            ->map(fn ($date) => $date->format('Y-m-d'));

        $calendarData = $paginatedUsers->through(function ($user) use ($dateRange) {
            return $this->formatSingleUserData($user, $dateRange);
        });

        $teamsForFilter = collect();
        if ($user->hasAnyRole(['admin', 'hr'])) {
            $teamsForFilter = Team::orderBy('name')->get(['id', 'name']);
        } elseif ($user->hasRole('team-lead')) {
            $teamsForFilter = $user->ledTeams()->select('id', 'name')->orderBy('name')->get();
        } else {
            // Regular employee: show teams they are part of
            $teamsForFilter = $user->teams()->select('id', 'name')->orderBy('name')->get();
        }

        return Inertia::render('Leave/Calendar', [
            'calendarData' => $calendarData,
            'dateRange' => $dateRange,
            'teams' => $teamsForFilter,
            'filters' => $filters,
            'authUserRole' => Auth::user()->roles->pluck('name')->first(),
        ]);
    }

    private function getValidatedFilters(Request $request): array
    {
        $defaults = [
            'start_date' => now()->startOfMonth()->format('Y-m-d'),
            'end_date' => now()->endOfMonth()->format('Y-m-d'),
            'show_absent_only' => false,
            'search' => null,
            'team_id' => null,
        ];
        $data = array_merge($defaults, $request->all());

        return Validator::make($data, [
            'search' => 'nullable|string|max:255',
            'team_id' => 'nullable|integer|exists:teams,id',
            'start_date' => 'required|date|before_or_equal:end_date',
            'end_date' => 'required|date',
            'show_absent_only' => 'required|boolean',
        ])->validate();
    }

    private function applyFilters($query, array $filters, User $user, Collection $ledTeamIds): void
    {
        if (!empty($filters['search'])) {
            $query->where('name', 'like', "%{$filters['search']}%");
        }

        if (!empty($filters['team_id'])) {
            if ($user->hasRole('team-lead') && !$ledTeamIds->contains($filters['team_id'])) {
                abort(403);
            }

            $query->whereHas('teams', function ($teamQuery) use ($filters) {
                $teamQuery->where('teams.id', $filters['team_id']);
            });
        }

        if ($filters['show_absent_only']) {
            $query->whereHas('leaveApplications', function ($q) use ($filters) {
                $startDate = Carbon::parse($filters['start_date']);
                $endDate = Carbon::parse($filters['end_date']);
                $q->approved()->overlapsWith($startDate, $endDate);
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
                        'color' => $this->getLeaveTypeColor($onLeave->leave_type),
                        'leave_type' => ucfirst($onLeave->leave_type),
                        'day_type' => $onLeave->day_type,
                    ];
                } else {
                    // Regular employee: no leave type details
                    $details = null;
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

    private function getLeaveTypeColor(string $leaveType): string
    {
        $colors = [
            'sick' => '#ff9800',
            'annual' => '#EF5350',
            'personal' => '#9C27B0',
            'emergency' => '#F44336',
            'maternity' => '#E91E63',
            'paternity' => '#3F51B5',
        ];

        return $colors[$leaveType] ?? '#607D8B';
    }
}
