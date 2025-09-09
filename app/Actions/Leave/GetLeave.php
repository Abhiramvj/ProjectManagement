<?php

namespace App\Actions\Leave;

use App\Models\Holiday;
use App\Models\LeaveApplication;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GetLeave
{
    /**
     * Determine the color category used for frontend display.
     */
    private function getLeaveColorCategory(LeaveApplication $request): string
    {
        if ($request->status === 'pending') {
            return 'pending';
        }

        return match ($request->leave_type) {
            'annual' => 'annual',
            'sick' => 'sick',
            'personal' => 'personal',
            'emergency' => 'emergency',
            'maternity', 'paternity' => $request->leave_type,
            'wfh' => 'wfh',
            'compensatory' => 'compensatory',
            default => 'unknown',
        };
    }

    /**
     * Fetch all necessary data for the Leave application page.
     * This version is optimized to NOT load all employees on initial page load.
     */
    public function handle(): array
    {
        $authUser = Auth::user();

        // Determine which user's data we need to display
        $displayUserId = request('user_id');
        $displayUser = $authUser; // Default to the logged-in user

        // If a different user is requested AND the logged-in user has permission...
        if ($displayUserId && $authUser->hasAnyRole(['admin', 'hr'])) {
            // ...then find that requested user to be the focus of the page.
            $displayUser = User::find($displayUserId) ?? $authUser;
        }

        if (is_array($displayUser)) {
            Log::error('displayUser is array:', $displayUser);
            $displayUser = User::find($displayUser['id']);
        } else {
            Log::info('displayUser is model', ['id' => $displayUser->id]);
        }

        // Calculate leave stats for the correct user ($displayUser)
        $leaveStats = [
            'annual' => ['taken' => $displayUser->approvedAnnualLeaves()->sum('leave_days'), 'total' => $displayUser->total_annual_leave],
            'sick' => ['taken' => $displayUser->approvedSickLeaves()->sum('leave_days'), 'total' => $displayUser->total_sick_leave],
            'personal' => ['taken' => $displayUser->approvedPersonalLeaves()->sum('leave_days'), 'total' => $displayUser->total_personal_leave],
        ];

        // Prepare the employees array for the frontend v-select.
        // If an employee is selected, we pass their full object so the dropdown displays their name correctly on load.
        $employees = [];
        if ($displayUser->id !== $authUser->id) {
            $employees[] = $displayUser->only('id', 'name', 'email');
        }

        // Build the query for calendar events for the correct user ($displayUser)
        $leaveEventsQuery = LeaveApplication::with('user:id,name')
            ->whereIn('status', ['pending', 'approved'])
            ->where('user_id', $displayUser->id);

        $leaveEvents = $leaveEventsQuery->get()
            ->map(fn ($request) => [
                'user_id' => $request->user_id,
                'start' => $request->start_date->toDateString(),
                'end' => $request->end_date ? $request->end_date->toDateString() : null,
                'title' => ucfirst($request->leave_type).' Leave',
                'class' => $request->status,
                'color_category' => $this->getLeaveColorCategory($request),
                'start_half_session' => $request->start_half_session,
                'end_half_session' => $request->end_half_session,
            ]);

        // Get holiday events (this is global and correct)
        $holidayEvents = Holiday::all()->map(fn ($holiday) => [
            'start' => $holiday->date->toDateString(),
            'end' => null,
            'title' => $holiday->name,
            'class' => 'holiday',
            'color_category' => 'holiday',
            'user_id' => null,
        ]);

        $highlighted = $leaveEvents->concat($holidayEvents)->values()->all();

        // "Your Requests" modal should ALWAYS show the LOGGED-IN user's requests
        $requests = LeaveApplication::with(['user:id,name'])
            ->where('user_id', $authUser->id)
            ->select([
                'id',
                'user_id',
                'start_date',
                'end_date',
                'reason',
                'leave_type',
                'status',
                'created_at',
                'rejection_reason',
                'leave_days',
                'supporting_document_path',
                'start_half_session',
                'end_half_session',
            ])
            ->orderByRaw("CASE status WHEN 'pending' THEN 1 WHEN 'approved' THEN 2 WHEN 'rejected' THEN 3 ELSE 4 END")
            ->latest()
            ->paginate(15);

        // Transform the collection to include proper document URLs
        $requests->getCollection()->transform(function ($leave) {
            $leave->supporting_document = $leave->supporting_document_path
                ? asset('storage/'.$leave->supporting_document_path)
                : null;

            return $leave;
        });

        $leaveTypes = config('leave_types');

        // Map into two objects for Vue
        $leaveTypeDescriptions = collect($leaveTypes)->mapWithKeys(function ($type, $key) {
            return [$key => [
                'title' => $type['title'],
                'summary' => $type['summary'],
                'details' => $type['details'],
            ]];
        })->toArray();

        $leaveTypeIcons = collect($leaveTypes)->mapWithKeys(function ($type, $key) {
            return [$key => $type['icon'] ?? '📌'];
        })->toArray();

        return [
            'leaveRequests' => $requests,
            'highlightedDates' => $highlighted,
            'remainingLeaveBalance' => $displayUser->leave_balance,
            'compOffBalance' => $displayUser->comp_off_balance,
            'employees' => $employees,
            'leaveStats' => $leaveStats,
            'canManage' => false,
            'leaveTypeDescriptions' => $leaveTypeDescriptions,
            'leaveTypeIcons' => $leaveTypeIcons,
        ];
    }
}
