<?php

namespace App\Http\Controllers;

use App\Actions\LeaveLog\GetLeaveLogsAction;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeaveLogController extends Controller
{
    protected GetLeaveLogsAction $getLeaveLogsAction;

    public function __construct(GetLeaveLogsAction $getLeaveLogsAction)
    {
        $this->getLeaveLogsAction = $getLeaveLogsAction;
    }

    public function index(Request $request)
    {
        $logs = $this->getLeaveLogsAction->execute($request);

        return Inertia::render('Leave/Logs', [
            'logs' => $logs,
            'employees' => User::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['employee_id']),
        ]);
    }
}
