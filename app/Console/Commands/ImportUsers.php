<?php

namespace App\Imports;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date; // Use the powerful Excel date helper

class ImportUsers implements ToModel, WithHeadingRow, WithValidation
{
    /**
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // This part is already correct and robustly handles the date parsing.
        $manager = User::where('name', $row['reports_to'])->first();

        $user = new User([
            'name' => $row['name'],
            'email' => $row['email'],
            'designation' => $row['designation'],
            'password' => Hash::make('password'),
            'work_mode' => $row['work_mode'],
            'parent_id' => $manager ? $manager->id : null,
            'hire_date' => $row['doj'] ? Carbon::instance(Date::excelToDateTimeObject($row['doj'])) : null,
            'birth_date' => $row['birth_date'] ? Carbon::instance(Date::excelToDateTimeObject($row['birth_date'])) : null,
        ]);

        $user->save();
        $user->assignRole($row['role']);

        return $user;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'designation' => 'required|string',
            'role' => 'required|string|exists:roles,name',

            // ====================================================================
            // === THE KEY FIX IS HERE ===
            // Change the strict 'date_format' rule to the flexible 'date' rule.
            'doj' => 'required|date',
            'birth_date' => 'nullable|date',
            // ====================================================================

            'reports_to' => 'nullable|string|exists:users,name',
            'work_mode' => 'nullable|string',
        ];
    }

    /**
     * @return array
     */
    public function customValidationMessages()
    {
        return [
            'email.unique' => 'The email on row :row has already been taken.',
            'role.exists' => 'The role on row :row is not a valid system role.',

            // Update the message to match the new, more flexible rule.
            'doj.date' => 'The Date of Joining (doj) on row :row is not a valid date format.',
            'birth_date.date' => 'The Birth Date on row :row is not a valid date format.',

            'reports_to.exists' => 'The manager listed on row :row was not found in the system.',
        ];
    }
}
