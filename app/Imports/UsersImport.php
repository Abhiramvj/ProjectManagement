<?php

namespace App\Imports;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class UsersImport implements ToModel, WithHeadingRow, WithValidation
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $manager = User::where('name', trim($row['reports_to'] ?? ''))->first();

        $user = new User([
            'name'        => trim($row['name']),
            'email'       => trim($row['email']),
            'designation' => trim($row['designation']),
            'password'    => Hash::make('password'),
            'work_mode'   => trim($row['work_mode']),
            'parent_id'   => $manager ? $manager->id : null,
            'hire_date'   => $this->transformDate($row['doj']),
            'birth_date'  => $this->transformDate($row['birth_date']),
        ]);

        $user->save();
        $user->assignRole(trim($row['role']));
        return $user;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'designation' => 'required|string',
            'role' => 'required|string|exists:roles,name',
            'doj' => ['required', $this->dateValidationRule()],
            'birth_date' => ['nullable', $this->dateValidationRule()],
            'reports_to' => 'nullable|string|exists:users,name',
            'work_mode' => 'nullable|string',
        ];
    }

    /**
     * A helper function to parse a date regardless of whether it's an
     * Excel date number or a date string.
     */
    private function transformDate($value): ?Carbon
    {
        if (!$value) {
            return null;
        }
        if (is_numeric($value)) {
            return Carbon::instance(Date::excelToDateTimeObject($value));
        }
        // Use createFromFormat for d/m/Y strings, fallback to parse for others
        try {
            return Carbon::createFromFormat('d/m/Y', $value);
        } catch (\Exception $e) {
            return Carbon::parse($value);
        }
    }

    /**
     * A reusable, robust custom validation rule for our flexible date checking.
     * This version is immune to the strtotime() parsing quirk.
     */
    private function dateValidationRule(): \Closure
    {
        return function ($attribute, $value, $fail) {
            // Case 1: It's a valid Excel date number. Pass.
            if (is_numeric($value)) {
                return;
            }
            // Case 2: It's a string. We will try to parse it explicitly.
            try {
                // Use Carbon's strict parser. If this succeeds, the date is valid.
                Carbon::createFromFormat('d/m/Y', $value);
            } catch (\Exception $e) {
                // If Carbon throws an exception, the format is wrong. Fail.
                $fail('The '.$attribute.' is not a valid date or is not in d/m/Y format.');
            }
        };
    }

    /**
     * @return array
     */
    public function customValidationMessages()
    {
        return [
            'email.unique' => 'The email on row :row has already been taken.',
            'role.exists' => 'The role on row :row is not a valid system role.',
            'reports_to.exists' => 'The manager on row :row was not found.',
        ];
    }
}
