<?php

namespace App\Http\Controllers;

use App\Actions\Review\BuildReviewSummariesAction;
use App\Actions\Review\BuildTeamStatsAction;
use App\Actions\Review\CreateSelfReviewsAction;
use App\Actions\Review\GetEmployeeReviewHistoryAction;
use App\Actions\Review\GetMyReviewsAction;
use App\Actions\Review\GetTeamMemberIdsRecursiveAction;
use App\Actions\Review\GetTeamMembersAction;
use App\Actions\Review\GetTeamReviewsAction;
use App\Actions\Review\StoreEmployeeReviewAction;
use App\Actions\Review\StoreSelfReviewAction;
use App\Http\Requests\Review\StoreEmployeeReviewRequest;
use App\Http\Requests\Review\StoreSelfReviewRequest;
use App\Models\PerformanceCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function myReviews(GetMyReviewsAction $getMyReviewsAction)
    {
        $data = $getMyReviewsAction->execute();

        return inertia('Reviews/Index', $data);
    }

    // Show form to create a new self review for given month/year
    public function createSelfReview(CreateSelfReviewsAction $createSelfReviewAction, int $month, int $year)
    {
        $data = $createSelfReviewAction->execute($month, $year);

        return inertia('Reviews/CreateReview', $data);
    }

    // Store the new self review submitted by logged-in user
    public function storeSelfReview(StoreSelfReviewRequest $request, int $month, int $year, StoreSelfReviewAction $storeSelfReviewAction): RedirectResponse
    {
        try {
            $storeSelfReviewAction->execute($request->validated()['scores'], $month, $year);

            return redirect()->back()->with('success', 'Review submitted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to submit review. Please try again.');
        }
    }

    public function teamReviews(
        GetTeamMemberIdsRecursiveAction $getTeamMemberIdsRecursiveAction,
        GetTeamMembersAction $getTeamMembersAction,
        GetTeamReviewsAction $getTeamReviewsAction,
        BuildReviewSummariesAction $buildReviewSummariesAction,
        BuildTeamStatsAction $buildTeamStatsAction
    ) {
        $user = Auth::user();

        $teamMemberIds = $getTeamMemberIdsRecursiveAction->execute($user->id);
        $teamMembers = $getTeamMembersAction->execute($teamMemberIds);
        $reviews = $getTeamReviewsAction->execute($teamMemberIds);
        $reviewSummaries = $buildReviewSummariesAction->execute($teamMembers, $reviews);
        $stats = $buildTeamStatsAction->execute($reviewSummaries);
        $categories = PerformanceCategory::with('criteria')->get();

        return inertia('Reviews/TeamReviews', [
            'members' => $reviewSummaries->toArray(),
            'stats' => $stats,
            'categories' => $categories->toArray(),
        ]);
    }

    public function showReviewHistory(Request $request, int $employeeId, GetEmployeeReviewHistoryAction $getEmployeeReviewHistoryAction)
    {
        $data = $getEmployeeReviewHistoryAction->execute($request, $employeeId);

        return inertia('Reviews/EmployeeHistory', $data);
    }

    public function storeEmployeeReview(StoreEmployeeReviewRequest $request, int $employeeId, int $month, int $year, StoreEmployeeReviewAction $storeEmployeeReviewAction)
    {
        try {
            $storeEmployeeReviewAction->execute(
                $employeeId,
                $month,
                $year,
                $request->validated('scores'),
                $request->validated('comments', [])
            );

            return redirect()->route('reviews.team', [
                'employeeId' => $employeeId,
                'month' => $month,
                'year' => $year,
            ])->with('success', 'Review submitted successfully.');
        } catch (\Throwable $e) {
            return back()->withErrors('Failed to save the review.');
        }
    }
}
