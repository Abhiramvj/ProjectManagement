<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateUserHierarchy implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;
    public $failOnTimeout = true;

    public function handle(): void
    {
        Log::info('HIERARCHY JOB: Starting process to build user reporting structure.');

        DB::transaction(function () {
            // Fetch users needing parent_id assignment in chunks to optimize memory
            User::whereNotNull('temp_reports_to')->whereNull('parent_id')
                ->chunkById(500, function ($usersChunk) {
                    // Collect unique manager names from current chunk
                    $managerNames = $usersChunk->pluck('temp_reports_to')->unique()->toArray();

                    // Fetch managers' ids mapped by name (case sensitive name matching)
                    $managers = User::whereIn('name', $managerNames)->pluck('id', 'name')->toArray();

                    if (empty($managers)) {
                        return; // No managers found, skip update for this chunk
                    }

                    // Prepare CASE statements for bulk update
                    $cases = [];
                    $userIds = [];

                    foreach ($usersChunk as $user) {
                        if (isset($managers[$user->temp_reports_to])) {
                            $userIds[] = $user->id;
                            // Use parameter binding with int casting for safety
                            $cases[$user->id] = (int)$managers[$user->temp_reports_to];
                        }
                    }

                    if (empty($userIds)) {
                        return; // No users to update in this chunk
                    }

                    // Build CASE SQL expression
                    $caseSql = 'CASE id ';
                    foreach ($cases as $id => $parentId) {
                        $caseSql .= "WHEN {$id} THEN {$parentId} ";
                    }
                    $caseSql .= 'END';

                    // Run single bulk update query
                    DB::table('users')
                        ->whereIn('id', $userIds)
                        ->update(['parent_id' => DB::raw($caseSql)]);
                });

            // Final cleanup: clear the temp_reports_to column from all users
            User::whereNotNull('temp_reports_to')->update(['temp_reports_to' => null]);
        });

        Log::info('HIERARCHY JOB: Finished successfully.');
    }

    public function failed(\Throwable $exception): void
    {
        Log::emergency("HIERARCHY JOB FAILED: The process to build the user hierarchy has failed. Reason: " . $exception->getMessage());
    }
}
