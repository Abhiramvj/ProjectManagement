<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PerformanceCategory;
use App\Models\PerformanceCriterion;

class PerformanceCriteriaTableSeeder extends Seeder
{
    public function run()
    {
        $categories = PerformanceCategory::all();

        foreach ($categories as $category) {
            switch($category->name) {
                case 'Technical Skills':
                    $criteria = [
                        'Total Number of Bugs Reported',
                        'Consistently Meets Deadlines',
                        'Documentation',
                        'Adherence to Coding Standards and Best Practices',
                        'Code Quality',
                    ];
                    break;

                case 'Learning and Development':
                    $criteria = [
                        'Completion of Training Courses or Certifications',
                        'Share Insights and Knowledge',
                    ];
                    break;

                case 'Collaboration':
                    $criteria = [
                        'Teamwork',
                        'Communication',
                    ];
                    break;

                case 'Initiative':
                    $criteria = [
                        'Proactive Approach',
                        'Innovation',
                    ];
                    break;

                default:
                    $criteria = [];
                    break;
            }

            foreach ($criteria as $criterion) {
                PerformanceCriterion::create([
                    'category_id' => $category->id,
                    'name' => $criterion,
                    'max_score' => 10,
                ]);
            }
        }
    }
}
