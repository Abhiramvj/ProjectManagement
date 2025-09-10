<?php

namespace Database\Seeders;

use App\Models\ReviewCategory;
use App\Models\ReviewCriteria;
use Illuminate\Database\Seeder;

class ReviewCriteriaSeeder extends Seeder
{
    public function run(): void
    {
        // Technical Skills (70%)
        $tech = ReviewCategory::firstOrCreate(['name' => 'Technical Skills'], ['weight' => 70]);
        ReviewCriteria::insert([
            ['category_id' => $tech->id, 'name' => 'Bugs Reported', 'max_points' => 10],
            ['category_id' => $tech->id, 'name' => 'Documentation', 'max_points' => 10],
            ['category_id' => $tech->id, 'name' => 'Code Quality', 'max_points' => 10],
            ['category_id' => $tech->id, 'name' => 'Completion Time', 'max_points' => 10],
        ]);

        // Learning & Development (15%)
        $ld = ReviewCategory::firstOrCreate(['name' => 'Learning & Development'], ['weight' => 15]);
        ReviewCriteria::insert([
            ['category_id' => $ld->id, 'name' => 'Training', 'max_points' => 10],
            ['category_id' => $ld->id, 'name' => 'Knowledge Sharing', 'max_points' => 10],
        ]);

        // Professionalism (15%)
        $pro = ReviewCategory::firstOrCreate(['name' => 'Professionalism'], ['weight' => 15]);
        ReviewCriteria::insert([
            ['category_id' => $pro->id, 'name' => 'Planned Leave', 'max_points' => 10],
            ['category_id' => $pro->id, 'name' => 'Timeliness', 'max_points' => 10],
        ]);
    }
}
