<?php

namespace App\Http\Controllers;

use App\Actions\TimeLog\GetTimeLogs;
use App\Actions\TimeLog\StoreTimeLogs;
use App\Http\Requests\TimeLog\StoreTimeLogRequest;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class TimeLogController extends Controller
{
    public function index(GetTimeLogs $getTimeLogs)
    {
        $data = $getTimeLogs->handle();

        return Inertia::render('Hours/Index', $data);
    }

    public function store(StoreTimeLogRequest $request, StoreTimeLogs $storeTimeLogs)
    {
        $storeTimeLogs->handle($request->validated());

        return Redirect::route('hours.index')->with('success', 'Working hours logged successfully.');
    }
}
