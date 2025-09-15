<?php

namespace App\Http\Controllers;

use App\Actions\LeaveCalendar\GetLeaveCalendarDataAction;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeaveCalendarController extends Controller
{
    protected GetLeaveCalendarDataAction $leaveCalendarAction;

    public function __construct(GetLeaveCalendarDataAction $leaveCalendarAction)
    {
        $this->leaveCalendarAction = $leaveCalendarAction;
    }

    /**
     * Display the leave calendar page
     */
    public function index(Request $request)
    {
        $data = $this->leaveCalendarAction->execute($request);

        return Inertia::render('Leave/Calendar', [
            'calendarData' => $data['calendarData'],
            'dateRange' => $data['dateRange'],
            'teams' => $data['teams'],
            'filters' => $data['filters'],
            'authUserRole' => $data['authUserRole'],
        ]);
    }
}
