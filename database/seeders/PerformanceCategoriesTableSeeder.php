<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PerformanceCategory;

class PerformanceCategoriesTableSeeder extends Seeder
{
    public function run()
    {
        $categories = [
            ['name' => 'Technical Skills', 'weight' => 40],
            ['name' => 'Learning and Development', 'weight' => 15],
            ['name' => 'Collaboration', 'weight' => 25],
            ['name' => 'Initiative', 'weight' => 20],
        ];

        foreach ($categories as $category) {
            PerformanceCategory::create($category);
        }
    }
}
