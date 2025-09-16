<?php 
namespace App\Actions\Review;

use App\Models\User;
use Illuminate\Http\Request;

class GetEmployeeReviewHistoryAction
{
    public function __construct(
        private GetEmployeeWithDetailsAction $getEmployeeWithDetailsAction,
        private GetCategoriesWithCriteriaAction $getCategoriesWithCriteriaAction,
        private GetLatestReviewPeriodAction $getLatestReviewPeriodAction,
        private GetEmployeeReviewsAction $getEmployeeReviewsAction,
        private BuildReviewMetricsAction $buildReviewMetricsAction,
        private FormatReviewAction $formatReviewAction
    ) {}

    public function execute(Request $request, int $employeeId): array
    {
        // Fetch employee + categories
        $employee = $this->getEmployeeWithDetailsAction->execute($employeeId);
        $categories = $this->getCategoriesWithCriteriaAction->execute();

        // Resolve review period
        $reviewMonth = $request->query('month');
        $reviewYear = $request->query('year');

        if (! $reviewMonth || ! $reviewYear) {
            $latest = $this->getLatestReviewPeriodAction->execute($employeeId);

            if (! $latest) {
                return [
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
                ];
            }

            $reviewMonth = $latest['month'];
            $reviewYear = $latest['year'];
        }

        // Get self + manager reviews
        $reviews = $this->getEmployeeReviewsAction->execute($employeeId, $reviewMonth, $reviewYear);
        $currentReview = $reviews['selfReview'] ?? $reviews['managerReview'];

        // Build metrics + scores
        $metricsData = $this->buildReviewMetricsAction->execute($currentReview, $categories);

        return [
            'member' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'role' => $employee->role,
                'email' => $employee->email,
            ],
            'reviewPeriod' => date('F Y', mktime(0, 0, 0, $reviewMonth, 1, $reviewYear)),
            'metrics' => $metricsData['metrics'],
            'overallScore' => $metricsData['overallScore'],
            'lastReview' => $metricsData['lastReviewDate'],
            'selfReview' => $this->formatReviewAction->execute($reviews['selfReview']),
            'managerReview' => $this->formatReviewAction->execute($reviews['managerReview']),
            'categories' => $categories,
        ];
    }
}
