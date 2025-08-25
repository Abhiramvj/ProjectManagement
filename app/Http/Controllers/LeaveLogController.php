<?php

namespace App\Http\Controllers;

use App\Models\LeaveLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeaveLogController extends Controller
{
    public function index(Request $request)
    {
        $query = LeaveLog::with(['user:id,name', 'actor:id,name'])
            ->latest();

        // Add filtering by employee
        if ($request->filled('employee_id')) {
            $query->where('user_id', $request->employee_id);
        }

        $logs = $query->paginate(20)->withQueryString();

        return Inertia::render('Leave/Logs', [
            'logs' => $logs,
            'employees' => User::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['employee_id']),
        ]);
    }
}
