<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Review;
use App\Models\ReviewScore;
use App\Models\PerformanceCriterion;
use Illuminate\Support\Facades\DB;

class SampleReviewsTableSeeder extends Seeder
{
    public function run()
    {
        $teamLeadId = 4;
        $employeeIds = [5, 6];
        $criteria = PerformanceCriterion::all();
        $numReviews = 50; // for each kind of review

        DB::transaction(function () use ($teamLeadId, $employeeIds, $criteria, $numReviews) {
            $faker = fake();

            // Create self reviews (reviewer is same as user)
            for ($i = 0; $i < $numReviews; $i++) {
                $employeeId = $faker->randomElement($employeeIds);
                $year = $faker->numberBetween(2022, 2025);
                $month = $faker->numberBetween(1, 12);

                $review = Review::updateOrCreate(
                    [
                        'user_id' => $employeeId,
                        'reviewer_id' => $employeeId,  // self review
                        'review_month' => $month,
                        'review_year' => $year,
                    ],
                    []
                );

                ReviewScore::where('review_id', $review->id)->delete();

                foreach ($criteria as $criterion) {
                    ReviewScore::create([
                        'review_id' => $review->id,
                        'criteria_id' => $criterion->id,
                        'score' => $faker->numberBetween(1, 10),
                    ]);
                }
            }

            // Create team lead reviews
            for ($i = 0; $i < $numReviews; $i++) {
                $employeeId = $faker->randomElement($employeeIds);
                $year = $faker->numberBetween(2022, 2025);
                $month = $faker->numberBetween(1, 12);

                $review = Review::updateOrCreate(
                    [
                        'user_id' => $employeeId,
                        'reviewer_id' => $teamLeadId,  // team lead review
                        'review_month' => $month,
                        'review_year' => $year,
                    ],
                    []
                );

                ReviewScore::where('review_id', $review->id)->delete();

                foreach ($criteria as $criterion) {
                    ReviewScore::create([
                        'review_id' => $review->id,
                        'criteria_id' => $criterion->id,
                        'score' => $faker->numberBetween(1, 10),
                    ]);
                }
            }
        });
    }
}
