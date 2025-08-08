<?php

namespace App\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Spatie\Permission\Models\Role;

class ImportHierarchyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:import-from-excel';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports users, roles, and manager relationships from an Excel file.';

    /**
     * Execute the console command.
     */
   public function handle()
{
    $filePath = storage_path('app/company_data.xlsx');

    if (! file_exists($filePath)) {
        $this->error("File not found at: {$filePath}");
        return Command::FAILURE;
    }

    $this->info("File found. Starting import...");

    $reader = new Xlsx;
    $spreadsheet = $reader->load($filePath);
    $excelData = $spreadsheet->getActiveSheet()->toArray();
    array_shift($excelData); // Remove header row

    DB::beginTransaction();
    try {
        // --- PASS 1: CREATE USERS AND ASSIGN ROLES ---
        $this->info('--- Pass 1: Creating user records and assigning roles ---');
        foreach ($excelData as $rowNumber => $row) {
            $name = $row[0] ?? null;
            if (empty($name)) {
                continue;
            }

            $email = strtolower(str_replace(' ', '.', $name)).'@company.com';
            if (User::where('email', $email)->exists()) {
                $this->warn("User '{$name}' with email '{$email}' already exists. Skipping insertion.");
                continue;
            }

            $parseDate = function ($dateString) {
                if (empty($dateString)) return null;
                // Try parsing as a standard date format first (e.g., from a proper export)
                try {
                    return Carbon::parse($dateString)->format('Y-m-d');
                } catch (Exception $e) {
                    // Fallback for Excel's integer date format
                    try {
                        return Carbon::createFromTimestamp(strtotime('1900-01-01') + ($dateString - 2) * 86400)->format('Y-m-d');
                    } catch(Exception $e2) {
                        return null; // Return null if all parsing fails
                    }
                }
            };

            // --- THE FIX: USE User::create() and get the model instance back ---
            // This method is secure because it respects the `$fillable` array on your User model.
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make('password'),
                'designation' => $row[1] ?? null,
                'hire_date' => $parseDate($row[2] ?? null),
                'birth_date' => $parseDate($row[3] ?? null),
                'leave_balance' => 20.00,
                'parent_id' => null,
                'work_mode' => $row[5] ?? null, // <<< --- CORRECTED LINE
            ]);

            // Now the $user variable exists and is a full Eloquent model.
            $this->line("Created user: {$user->name}");

            // Role Assignment Logic now works correctly.
            $processedRoleName = strtolower(trim($row[6] ?? ''));
            if (!empty($processedRoleName)) {
                // Using query caching for performance
                $role = Role::query()->where('name', $processedRoleName)->first();
                if ($role) {
                    $user->assignRole($role);
                    $this->line(" -> SUCCESS: Role '{$role->name}' found and assigned.");
                } else {
                    $this->warn(" -> WARNING: Role '{$processedRoleName}' was not found in the database.");
                }
            } else {
                    $this->warn(" -> WARNING: No role specified in column G for {$user->name}.");
            }
        }

        // --- PASS 2: LINK MANAGERS ---
        $this->info('--- Pass 2: Linking managers ---');
        $allUsers = User::all()->keyBy('name'); // Key by name for faster lookups
        foreach ($excelData as $row) {
            $employeeName = $row[0] ?? null;
            $managerName = $row[4] ?? null;
            if (empty($employeeName) || empty($managerName)) {
                continue;
            }

            $employeeUser = $allUsers->get($employeeName);
            $managerUser = $allUsers->get($managerName);

            if ($employeeUser && $managerUser) {
                $employeeUser->update(['parent_id' => $managerUser->id]);
                $this->line("Linked {$employeeName} -> {$managerName}");
            } else if (!$managerUser) {
                $this->warn("Could not create link for '{$employeeName}'. Manager '{$managerName}' was not found.");
            }
        }

        DB::commit();
        $this->info('------------------------------------');
        $this->info('User import completed successfully! Data has been saved.');

        return Command::SUCCESS;

    } catch (Exception $e) {
        DB::rollBack();
        $this->error('A critical error occurred. Transaction rolled back. Nothing was saved.');
        $this->error('Error Message: '.$e->getMessage());
        $this->error('File: '.$e->getFile().' on line '.$e->getLine());

        return Command::FAILURE;
    }
}
}
