<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Badge;

class BadgeSeeder extends Seeder
{
    public function run()
    {
        Badge::firstOrCreate([
            'name' => 'Bronze',
        ], [
            'description' => 'Awarded for completing 1 session',
            'icon' => 'badges/bronze.png',
        ]);

        Badge::firstOrCreate([
            'name' => 'Silver',
        ], [
            'description' => 'Awarded for completing 3 sessions',
            'icon' => 'badges/silver.png',
        ]);

        Badge::firstOrCreate([
            'name' => 'Gold',
        ], [
            'description' => 'Awarded for completing 5 sessions',
            'icon' => 'badges/gold.png',
        ]);
    }
}
