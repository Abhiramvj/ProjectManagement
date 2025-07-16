<?php

namespace App\Http\Controllers;

use App\Actions\Performance\ShowPerformance;
use App\Models\User;
use Inertia\Inertia;

class PerformanceReportController extends Controller
{
    public function show(User $user, ShowPerformance $performance)
    {
        return Inertia::render('Performance/Show', $performance->handle($user));
    }
}
