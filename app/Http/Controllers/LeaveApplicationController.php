<?php
namespace App\Http\Controllers;

use App\Actions\Leave\GetLeave;
use App\Actions\Leave\StoreLeave;
use App\Actions\Leave\UpdateLeave;
use App\Http\Requests\Leave\StoreLeaveRequest;
use App\Http\Requests\Leave\UpdateLeaveRequest;
use App\Models\LeaveApplication;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Models\Team;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;

class LeaveApplicationController extends Controller
{
    public function index(GetLeave $getLeaveRequests)
    {
        return Inertia::render('Leave/Index', $getLeaveRequests->handle());
    }

    public function store(StoreLeaveRequest $request, StoreLeave $storeLeave)
    {
        try {
            $storeLeave->handle($request->validated());

            return Redirect::route('leave.index')->with('success', 'Leave application submitted.');
        } catch (\Exception $e) {
            return Redirect::back()->withErrors(['leave' => $e->getMessage()])->withInput();
        }
    }

    public function update(UpdateLeaveRequest $request, LeaveApplication $leave_application, UpdateLeave $updateLeaveStatus)
    {
        $updateLeaveStatus->handle($leave_application, $request->validated()['status']);

        return Redirect::back()->with('success', 'Application status updated.');
    }

    public function calendar(Request $request)
    {
        // Log incoming request for debugging
        Log::info('Calendar request received', [
            'query_params' => $request->all(),
            'user_id' => Auth::id()
        ]);

        $validated = $request->validate([
            'start_date'  => 'nullable|date_format:Y-m-d',
            'end_date'    => 'nullable|date_format:Y-m-d',
            'team_id'     => 'nullable|integer|exists:teams,id',
            'search'      => 'nullable|string|max:100',
            'absent_only' => 'nullable|in:1,true',
        ]);

        // Better date handling with logging
        $startDate = isset($validated['start_date']) && !empty($validated['start_date'])
            ? Carbon::parse($validated['start_date'])->startOfDay()
            : Carbon::now()->startOfMonth();

        $endDate = isset($validated['end_date']) && !empty($validated['end_date'])
            ? Carbon::parse($validated['end_date'])->endOfDay()
            : (isset($validated['start_date']) && !empty($validated['start_date'])
                ? Carbon::parse($validated['start_date'])->endOfDay()  // Same day if only start_date provided
                : Carbon::now()->endOfMonth());

        // Ensure end date is not before start date
        if ($endDate->lt($startDate)) {
            $endDate = $startDate->copy()->endOfDay();
        }

        Log::info('Date range calculated', [
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'validated' => $validated
        ]);

        // Build users query
        $usersQuery = User::query()
            ->whereHas('roles', fn($q) => $q->whereIn('name', ['employee', 'team-lead', 'project-manager']));

        // Apply team filter - using many-to-many relationship
        if (!empty($validated['team_id'])) {
            $usersQuery->whereHas('teams', function($q) use ($validated) {
                $q->where('teams.id', $validated['team_id']);
            });
            Log::info('Applied team filter', ['team_id' => $validated['team_id']]);
        }

        // Apply search filter
        if (!empty($validated['search'])) {
            $usersQuery->where('name', 'like', '%' . $validated['search'] . '%');
            Log::info('Applied search filter', ['search' => $validated['search']]);
        }

        // Apply absent only filter
        if (!empty($validated['absent_only'])) {
            $usersQuery->whereHas('leaveApplications', function($q) use ($startDate, $endDate) {
                $q->where('status', 'approved')
                  ->where(function($query) use ($startDate, $endDate) {
                      // Leave overlaps with date range
                      $query->where(function($q) use ($startDate, $endDate) {
                          $q->where('start_date', '<=', $endDate->toDateString())
                            ->where('end_date', '>=', $startDate->toDateString());
                      });
                  });
            });
            Log::info('Applied absent only filter');
        }

        // Get users with their leave applications
        $usersWithLeaves = $usersQuery
            ->select('id', 'name')
            ->with([
                'teams:id,name', // Load team relationship
                'leaveApplications' => function ($query) use ($startDate, $endDate) {
                    $query->select('id', 'user_id', 'start_date', 'end_date', 'reason', 'status')
                          ->where('status', 'approved')
                          ->where(function($q) use ($startDate, $endDate) {
                              // Leave overlaps with date range
                              $q->where('start_date', '<=', $endDate->toDateString())
                                ->where('end_date', '>=', $startDate->toDateString());
                          });
                }
            ])
            ->orderBy('name')
            ->get();

        Log::info('Query results', [
            'users_count' => $usersWithLeaves->count(),
            'users_with_leaves' => $usersWithLeaves->filter(function($user) {
                return $user->leaveApplications->count() > 0;
            })->count()
        ]);

        // Get all teams for filter dropdown
        $teams = Team::select('id', 'name')->orderBy('name')->get();

        $response = [
            'usersWithLeaves' => $usersWithLeaves,
            'teams'           => $teams,
            'filters'         => $validated,
            'dateRange'       => [
                'start' => $startDate->toDateString(),
                'end'   => $endDate->toDateString(),
            ],
        ];

        Log::info('Sending response', [
            'date_range' => $response['dateRange'],
            'filters' => $response['filters'],
            'users_count' => $usersWithLeaves->count()
        ]);

        return Inertia::render('Leave/Calendar', $response);
    }
}
