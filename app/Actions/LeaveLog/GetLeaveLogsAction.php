<?php

namespace App\Actions\LeaveLog;

use App\Models\LeaveLog;
use Illuminate\Http\Request;

class GetLeaveLogsAction
{
    public function execute(Request $request)
    {
        $query = LeaveLog::with(['user:id,name', 'actor:id,name'])
            ->latest();

        // Filtering by employee
        if ($request->filled('employee_id')) {
            $query->where('user_id', $request->employee_id);
        }

        return $query->paginate(20)->withQueryString();
    }
}
