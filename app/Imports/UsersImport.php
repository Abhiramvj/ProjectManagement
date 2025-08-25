<?php

namespace App\Imports;

use App\Jobs\RoleAssignmentJob;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Validators\ValidationException;
use Spatie\Permission\Models\Role;

class UsersImport implements ToCollection, WithChunkReading, WithHeadingRow
{
    private $roles;
    private static $emailsInFile = [];
    private $existingEmails;
    private $hashedPassword;

    public function __construct()
    {
        $this->roles = Role::all()->keyBy(fn($role) => strtolower($role->name));
        self::$emailsInFile = [];
        $this->existingEmails = User::pluck('email')->map(fn($email) => strtolower($email))->flip();

        // Hash the default password once
        $this->hashedPassword = Hash::make('password');
    }

    public function collection(Collection $rows)
    {
        $emailsInChunk = $rows->pluck('email')->filter()->unique()
            ->map(fn ($email) => strtolower(trim($email)));

        $this->validateChunk($rows, $emailsInChunk);

        $usersToInsert = [];
        $now = now();
        foreach ($rows as $row) {
            $usersToInsert[] = [
                'name' => trim($row['name']),
                'email' => trim($row['email']),
                'designation' => trim($row['designation']),
                'password' => $this->hashedPassword,  // reuse hashed password
                'work_mode' => trim($row['work_mode']),
                'temp_reports_to' => trim($row['reports_to'] ?? null),
                'parent_id' => null,
                'hire_date' => $this->transformDate($row['doj']),
                'birth_date' => $this->transformDate($row['birth_date']),
                'created_at' => $now,
                'updated_at' => $now,
            ];
            self::$emailsInFile[strtolower(trim($row['email']))] = true;
        }

        if (empty($usersToInsert)) {
            return;
        }

        DB::table('users')->insert($usersToInsert);

        // Instead of immediate role assignment, dispatch a job with inserted emails and roles for async processing
        $insertedEmails = collect($usersToInsert)->pluck('email');
        RoleAssignmentJob::dispatch($insertedEmails->toArray(), $rows->pluck('role')->toArray());

    }

    private function validateChunk(Collection $rows, Collection $emailsInChunk)
    {
        $errors = [];
        foreach ($rows as $key => $row) {
            $rowNumber = $key + 2;
            $email = strtolower(trim($row['email'] ?? ''));
            $roleName = strtolower(trim($row['role'] ?? ''));

            if (!$this->roles->has($roleName)) {
                $errors[$rowNumber][] = 'Role "' . $row['role'] . '" does not exist.';
            }
            // Check against preloaded existing emails from DB
            if ($this->existingEmails->has($email)) {
                $errors[$rowNumber][] = "Email '{$email}' already exists in the database.";
            }
            // Check duplicates within the same import file (across chunks)
            if (isset(self::$emailsInFile[$email])) {
                $errors[$rowNumber][] = "Email '{$email}' is duplicated within the import file.";
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }


    private function transformDate($value): ?string
    {
        if (!$value) return null;
        if (is_numeric($value)) {
            return Carbon::createFromTimestamp(intval(($value - 25569) * 86400))->toDateTimeString();
        }
        try {
            return Carbon::createFromFormat('d/m/Y', $value)->toDateTimeString();
        } catch (\Exception $e) {
            return Carbon::parse($value)->toDateTimeString();
        }
    }
}
