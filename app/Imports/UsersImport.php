<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;
use Carbon\Carbon;

class UsersImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        try {
            // Convert date of joining
            $hireDate = Carbon::createFromFormat('d/m/Y', $row['doj_ddmmyyyy'])->format('Y-m-d');

            // Lookup manager by name
            $manager = User::where('name', $row['report_to'])->first();

            return new User([
                'name' => $row['name'],
                'designation' => $row['designation'],
                'hire_date' => $hireDate,
                'email' => Str::slug($row['name'], '.') . '@company.com',
                'password' => bcrypt('defaultpassword'), // use hash
                'employee_id' => strtoupper(Str::random(6)),
                'parent_id' => $manager ? $manager->id : null,
            ]);
        } catch (\Exception $e) {
            logger()->error("Row import failed: " . $e->getMessage());
            return null;
        }
    }
}
