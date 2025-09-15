<?php

namespace App\Http\Controllers;

use App\Actions\GenerateMySummaryAction;
use App\Actions\Performance\GeneratePerformanceSummaryAction;
use App\Actions\Performance\ShowPerformanceAction;
use App\Http\Requests\Performance\GenerateMySummaryRequest;
use App\Http\Requests\Performance\GeneratePerformanceSummaryRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PerformanceReportController extends Controller
{
    public function show(User $user, ShowPerformanceAction $action): \Inertia\Response
    {
        return Inertia::render('Performance/Show', $action->handle($user));
    }

    /**
     * Generate an AI performance summary for a given user.
     */
    public function generateSummary(GeneratePerformanceSummaryRequest $request, User $user, GeneratePerformanceSummaryAction $summaryAction)
    {
        $stats = $request->validated();

        $summary = $summaryAction->handle($stats, $user);

        if (! $summary) {
            return response()->json([
                'error' => 'The AI summary could not be generated at this time.',
            ], 500);
        }

        return response()->json([
            'summary' => $summary,
        ]);
    }

    public function generateMySummary(GenerateMySummaryRequest $request, GenerateMySummaryAction $summaryAction)
    {
        $stats = $request->validated();
        $user = Auth::user();

        $summary = $summaryAction->handle($stats, $user);

        if (! $summary) {
            return response()->json([
                'error' => 'The AI summary could not be generated at this time.',
            ], 500);
        }

        return response()->json([
            'summary' => $summary,
        ]);
    }
}
