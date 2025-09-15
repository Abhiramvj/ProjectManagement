<?php

namespace App\Http\Controllers;

use App\Actions\TimeLog\GetTimeLogsAction;
use App\Actions\TimeLog\StoreTimeLogsAction;
use App\Http\Requests\TimeLog\StoreTimeLogRequest;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class TimeLogController extends Controller
{
    public function index(GetTimeLogsAction $getTimeLogs)
    {
        $data = $getTimeLogs->execute();

        return Inertia::render('Hours/Index', $data);
    }

    public function store(StoreTimeLogRequest $request, StoreTimeLogsAction $storeTimeLogs)
    {
        $storeTimeLogs->execute($request->validated());

        return Redirect::route('hours.index')->with('success', 'Working hours logged successfully.');
    }
}
