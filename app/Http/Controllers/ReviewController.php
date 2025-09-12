<?php

namespace App\Http\Controllers;

use App\Models\PerformanceCategory;
use App\Models\Review;
use App\Models\ReviewScore;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    // Show the logged-in user's self reviews
    public function myReviews()
    {
        $user = Auth::user();

        $reviews = Review::where('user_id', $user->id)
            ->where('reviewer_id', $user->id)
            ->with('scores.criterion.category')
            ->orderBy('review_year', 'desc')
            ->orderBy('review_month', 'desc')
            ->paginate(10); // paginate instead of get()

        $categories = PerformanceCategory::with('criteria')->get();

        return inertia('Reviews/Index', [
            'reviews' => $reviews->items(),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
                'from' => $reviews->firstItem(),
                'to' => $reviews->lastItem(),
            ],
            'links' => $reviews->linkCollection()->toArray(), // or $reviews->links()
            'categories' => $categories,
        ]);

    }

    // Show form to create a new self review for given month/year
    public function createSelfReview(Request $request, $month, $year)
    {
        $user = Auth::user();

        $categories = PerformanceCategory::with('criteria')->get();

        return inertia('Reviews/CreateReview', [
            'employee' => [
                'id' => $user->id,
                'name' => $user->name,
                'designation' => $user->designation ?? 'Employee',
            ],
            'reviewPeriod' => date('F Y', mktime(0, 0, 0, $month, 1, $year)),
            'categories' => $categories,
            'month' => $month,
            'year' => $year,
        ]);
    }

    // Store the new self review submitted by logged-in user
    public function storeSelfReview(Request $request, $month, $year)
    {
        \Log::info('RedirectTo value:', ['redirectTo' => $request->input('redirectTo')]);

        $request->validate([
            'scores' => 'required|array',
            'scores.*' => 'integer|min:1|max:10',
        ]);

        $user = Auth::user();

        DB::beginTransaction();

        try {
            $review = Review::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'reviewer_id' => $user->id,
                    'review_month' => $month,
                    'review_year' => $year,
                ],
                []
            );

            foreach ($request->scores as $criteriaId => $score) {
                ReviewScore::updateOrCreate(
                    [
                        'review_id' => $review->id,
                        'criteria_id' => $criteriaId,
                    ],
                    [
                        'score' => $score,
                    ]
                );
            }

            DB::commit();

            // Return JSON response instead of redirect
            return redirect()->back()->with('success', 'Review submitted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Failed to submit review. Please try again.');
        }
    }

    protected function getTeamMemberIdsRecursive($userId)
    {
        $directMembers = \App\Models\User::where('parent_id', $userId)->pluck('id');
        $allMembers = $directMembers->toBase();

        foreach ($directMembers as $memberId) {
            $allMembers = $allMembers->merge($this->getTeamMemberIdsRecursive($memberId));
        }

        return $allMembers;
    }

    // Show team members' reviews for team lead
    public function teamReviews()
    {
        $user = Auth::user();

        // Get team member IDs under this team lead
        $teamMemberIds = $this->getTeamMemberIdsRecursive($user->id)->unique();

        // Load users with role/designation and email
        $teamMembers = \App\Models\User::whereIn('id', $teamMemberIds)
            ->select('id', 'name', 'email', 'designation') // assuming 'designation' column exists for role
            ->get();

        // Get latest review per member, calculate summary scores, and review status
        $reviews = Review::whereIn('user_id', $teamMemberIds)
            ->with(['scores.criterion.category', 'reviewer'])
            ->orderBy('review_year', 'desc')
            ->orderBy('review_month', 'desc')
            ->get();

        // Aggregate reviews grouped by user_id to get latest and summary
        $reviewSummaries = $teamMembers->map(function ($member) use ($reviews) {
            $memberReviews = $reviews->where('user_id', $member->id);

            // Use latest review (first in ordered collection)
            $latestReview = $memberReviews->first();

            // Calculate average performance score (weighted)
            $avgScore = 0;
            $totalWeight = 0;

            if ($latestReview && $latestReview->scores && $latestReview->scores->count()) {
                $scoresByCategory = $latestReview->scores->groupBy(function ($score) {
                    return optional($score->criterion->category)->id;
                });

                foreach ($scoresByCategory as $categoryId => $scores) {
                    $categoryWeight = optional($scores->first()->criterion->category)->weight ?? 0;
                    $categoryAvg = $scores->avg('score');
                    if ($categoryWeight > 0 && $categoryAvg > 0) {
                        $avgScore += ($categoryAvg * $categoryWeight);
                        $totalWeight += $categoryWeight;
                    }
                }
            }

            $avgScore = $totalWeight ? ($avgScore / $totalWeight) : 0;

            // Calculate progress % (simple example: avg score * 10)
            $progress = round($avgScore * 10);

            // Determine status
            $status = $latestReview ? 'Completed' : 'Pending';

            return [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $member->designation ?? 'Employee',
                'performance' => round($avgScore, 1),
                'progress' => $progress > 100 ? 100 : $progress,
                'lastReview' => $latestReview ? $latestReview->review_month.'/'.$latestReview->review_year : 'N/A',
                'status' => $status,
                'latestReviewMonth' => $latestReview ? $latestReview->review_month : null,
                'latestReviewYear' => $latestReview ? $latestReview->review_year : null,
            ];

        });

        // Fetch all categories with criteria for frontend use
        $categories = PerformanceCategory::with('criteria')->get();

        // Aggregate stats for dashboard cards
        $stats = [
            'teamSize' => $teamMembers->count(),
            'avgPerformance' => $reviewSummaries->avg('performance'),
            'reviewsPending' => $reviewSummaries->where('status', 'Pending')->count(),
            'topPerformers' => $reviewSummaries->where('performance', '>', 8.5)->count(),
        ];

        return inertia('Reviews/TeamReviews', [
            'members' => $reviewSummaries->toArray(),
            'stats' => $stats,
            'categories' => $categories->toArray(),
        ]);

    }

    // Store a new review

    public function showReviewHistory(Request $request, $employeeId)
    {
        $employee = User::select('id', 'name', 'email', 'designation as role', 'hire_date')
            ->findOrFail($employeeId);

        // Load all categories and their criteria
        $categories = PerformanceCategory::with('criteria')->get();

        // Accept optional month/year query parameters
        $reviewMonth = $request->query('month');
        $reviewYear = $request->query('year');

        if (! $reviewMonth || ! $reviewYear) {
            // If not provided, get latest review period
            $latestReview = Review::where('user_id', $employeeId)
                ->orderByDesc('review_year')
                ->orderByDesc('review_month')
                ->with(['scores'])
                ->first();

            if (! $latestReview) {
                // No reviews found, return empty/default data
                return inertia('Reviews/EmployeeHistory', [
                    'member' => [
                        'id' => $employee->id,
                        'name' => $employee->name,
                        'role' => $employee->role,
                        'email' => $employee->email,
                    ],
                    'reviewPeriod' => null,
                    'metrics' => [],
                    'overallScore' => null,
                    'lastReview' => null,
                    'selfReview' => ['scores' => [], 'comments' => []],
                    'managerReview' => ['scores' => [], 'comments' => []],
                    'categories' => $categories,
                ]);
            }

            $reviewMonth = $latestReview->review_month;
            $reviewYear = $latestReview->review_year;
        }

        // Fetch Self Review (reviewer_id == user)
        $selfReview = Review::where('user_id', $employeeId)
            ->where('reviewer_id', $employeeId)
            ->where('review_month', $reviewMonth)
            ->where('review_year', $reviewYear)
            ->with('scores')
            ->first();

        // Fetch Manager Review (reviewer_id != user)
        $managerReview = Review::where('user_id', $employeeId)
            ->where('reviewer_id', '!=', $employeeId)
            ->where('review_month', $reviewMonth)
            ->where('review_year', $reviewYear)
            ->with('scores')
            ->first();

        // Fetch the review to get current review scores (can use selfReview if exists, else managerReview, or fallback)
        $currentReview = $selfReview ?? $managerReview;

        $currentMetrics = [];
        $overallScore = null;
        $lastReviewDate = null;

        if ($currentReview) {
            // Prepare performance metrics grouped by category
            foreach ($categories as $cat) {
                $catScores = $currentReview->scores->whereIn('criteria_id', $cat->criteria->pluck('id'));
                if ($catScores->count()) {
                    $currentMetrics[] = [
                        'name' => $cat->name,
                        'value' => round($catScores->avg('score'), 1),
                    ];
                }
            }

            $overallScore = round($currentReview->scores->avg('score'), 1);
            $lastReviewDate = $currentReview->created_at ? $currentReview->created_at->format('n/j/Y') : null;
        }

        // Helper function to format review scores and comments
        $formatReview = function ($review) {
            if (! $review) {
                return ['scores' => [], 'comments' => []];
            }
            $scores = [];
            $comments = [];
            foreach ($review->scores as $score) {
                $scores[$score->criteria_id] = $score->score;
                $comments[$score->criteria_id] = $score->comment ?? '';
            }

            return compact('scores', 'comments');
        };

        return inertia('Reviews/EmployeeHistory', [
            'member' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'role' => $employee->role,
                'email' => $employee->email,
            ],
            'reviewPeriod' => date('F Y', mktime(0, 0, 0, $reviewMonth, 1, $reviewYear)),
            'metrics' => $currentMetrics,
            'overallScore' => $overallScore,
            'lastReview' => $lastReviewDate,
            'selfReview' => $formatReview($selfReview),
            'managerReview' => $formatReview($managerReview),
            'categories' => $categories,
        ]);
    }

    public function storeEmployeeReview(Request $request, $employeeId, $month, $year)
    {
        $request->validate([
            'scores' => 'required|array',
            'scores.*' => 'integer|min:1|max:10',
            'comments' => 'nullable|array',
            'comments.*' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $review = Review::updateOrCreate(
                [
                    'user_id' => $employeeId,
                    'reviewer_id' => Auth::id(),
                    'review_month' => $month,
                    'review_year' => $year,
                ],
                []
            );

            foreach ($request->scores as $criteriaId => $score) {
                ReviewScore::updateOrCreate(
                    [
                        'review_id' => $review->id,
                        'criteria_id' => $criteriaId,
                    ],
                    [
                        'score' => $score,
                        'comment' => $request->comments[$criteriaId] ?? null,
                    ]
                );
            }

            DB::commit();

            return redirect()->route('reviews.team', [
                'employeeId' => $employeeId,
                'month' => $month,
                'year' => $year,
            ])->with('success', 'Review submitted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors('Failed to save the review.');
        }
    }
}
