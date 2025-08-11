<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HolidaySeeder extends Seeder
{
    public function run()
    {
        $holidays = [
            ['date' => '2025-01-26', 'name' => 'Republic Day',     'type' => 'public',  'is_recurring' => true],
            ['date' => '2025-08-15', 'name' => 'Independence Day', 'type' => 'public',  'is_recurring' => true],
            ['date' => '2025-10-02', 'name' => 'Gandhi Jayanti',   'type' => 'public',  'is_recurring' => true],
            ['date' => '2025-12-25', 'name' => 'Christmas',        'type' => 'public',  'is_recurring' => true],
            ['date' => '2025-12-31', 'name' => 'New Year\'s Eve',  'type' => 'company', 'is_recurring' => true],
            ['date' => '2025-01-01', 'name' => 'New Year',         'type' => 'public',  'is_recurring' => true],
            ['date' => '2025-04-14', 'name' => 'Vishu',            'type' => 'public',  'is_recurring' => true],
            ['date' => '2025-05-01', 'name' => 'Labour Day',       'type' => 'public',  'is_recurring' => true],
            ['date' => '2025-06-06', 'name' => 'Bhakrid',          'type' => 'public',  'is_recurring' => true],
            ['date' => '2025-10-01', 'name' => 'Navami',           'type' => 'company', 'is_recurring' => true],
            ['date' => '2025-12-24', 'name' => 'Christmas Eve',    'type' => 'company', 'is_recurring' => true],
            ['date' => '2025-03-31', 'name' => 'Eid',              'type' => 'public',  'is_recurring' => true],
            ['date' => '2025-04-18', 'name' => 'Good Friday',      'type' => 'public',  'is_recurring' => true],
            ['date' => '2025-09-09', 'name' => 'Onam',             'type' => 'public',  'is_recurring' => true],
        ];

        foreach ($holidays as $holiday) {
            DB::table('holidays')->updateOrInsert(
                ['date' => $holiday['date'], 'name' => $holiday['name']],
                [
                    'type' => $holiday['type'],
                    'is_recurring' => $holiday['is_recurring'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
