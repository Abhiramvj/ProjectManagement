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
    protected $signature = 'users:import-from-excel';

    protected $description = 'Imports users, roles, and manager relationships from an Excel file.';

    public function handle()
    {
        $filePath = storage_path('app/company_data.xlsx');

        if (! file_exists($filePath)) {
            $this->error("File not found at: {$filePath}");

            return Command::FAILURE;
        }

        $this->info('File found. Starting import...');

        $reader = new Xlsx;
        $spreadsheet = $reader->load($filePath);
        $excelData = $spreadsheet->getActiveSheet()->toArray();
        array_shift($excelData); // Remove header

        DB::beginTransaction();

        $createdCount = 0;
        $skippedCount = 0;
        $roleWarnings = [];

        try {
            $this->info('--- Pass 1: Creating users and assigning roles ---');
            foreach ($excelData as $row) {
                $name = $row[0] ?? null;
                if (empty($name)) {
                    $skippedCount++;

                    continue;
                }

                $email = strtolower(str_replace(' ', '.', $name)).'@company.com';
                if (User::where('email', $email)->exists()) {
                    $this->warn("User '{$name}' with email '{$email}' already exists. Skipping.");
                    $skippedCount++;

                    continue;
                }

                $parseDate = function ($dateString) {
                    if (empty($dateString)) {
                        return null;
                    }
                    try {
                        return Carbon::parse($dateString)->format('Y-m-d');
                    } catch (Exception $e) {
                        try {
                            return Carbon::createFromTimestamp(strtotime('1900-01-01') + ($dateString - 2) * 86400)->format('Y-m-d');
                        } catch (Exception $e2) {
                            return null;
                        }
                    }
                };

                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make('password'),
                    'designation' => $row[1] ?? null,
                    'hire_date' => $parseDate($row[2] ?? null),
                    'birth_date' => $parseDate($row[3] ?? null),
                    'leave_balance' => 20.00,
                    'parent_id' => null,
                    'work_mode' => $row[5] ?? null,
                ]);

                $processedRoleName = strtolower(trim($row[6] ?? ''));
                if (! empty($processedRoleName)) {
                    $role = Role::where('name', $processedRoleName)->first();
                    if ($role) {
                        $user->assignRole($role);
                        $createdCount++;
                    } else {
                        $roleWarnings[] = "Role '{$processedRoleName}' not found for user '{$name}'.";
                        $skippedCount++;
                    }
                } else {
                    $roleWarnings[] = "No role specified in column G for user '{$name}'.";
                    $skippedCount++;
                }
            }

            $this->info('--- Pass 2: Linking managers ---');
            $allUsers = User::all()->keyBy('name');
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
                } elseif (! $managerUser) {
                    $this->warn("Manager '{$managerName}' not found for employee '{$employeeName}'.");
                }
            }

            DB::commit();

            $this->info('------------------------------------');
            $this->info("User import completed! {$createdCount} users created, {$skippedCount} skipped.");
            foreach ($roleWarnings as $warning) {
                $this->warn($warning);
            }

            return Command::SUCCESS;

        } catch (Exception $e) {
            DB::rollBack();
            $this->error('Critical error occurred. Transaction rolled back.');
            $this->error('Error: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
