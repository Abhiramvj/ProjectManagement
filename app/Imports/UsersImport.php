<?php

namespace App\Imports;

use App\Models\User;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UsersImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new User([
            'name' => $row['name'],
            'designation' => $row['designation'],
            'hire_date' => $this->formatDate($row['doj_dd_mm_yyyy']),
            'birth_date' => $this->formatBirthDate($row['birth_date_dd_mm']),
            'parent_id' => $this->findManagerId($row['report_to']),
            'work_mode' => $row['work_mode'],
            'role' => $row['role'],
        ]);
    }

    private function formatDate($value)
    {
        return $value ? Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d') : null;
    }

    private function formatBirthDate($value)
    {
        return $value ? Carbon::createFromFormat('d/m', $value)->format('m-d') : null;
    }

    private function findManagerId($managerName)
    {
        if (! $managerName) {
            return null;
        }

        $manager = User::where('name', $managerName)->first();

        return $manager ? $manager->id : null;
    }
}
