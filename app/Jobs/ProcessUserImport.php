<?php

namespace App\Jobs;

use App\Events\UserImportCompleted;
use App\Imports\UsersImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Validators\ValidationException;

class ProcessUserImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;
    public $failOnTimeout = true;
    protected $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    public function handle(): void
    {
        try {
            // Step 1: Run the simplified, robust import.
            Excel::import(new UsersImport(), $this->filePath);

            Log::info("IMPORT JOB: Successfully finished initial data import for file: {$this->filePath}.");

            // Step 2: Dispatch the next job in the pipeline to build the hierarchy.
            UpdateUserHierarchy::dispatch()->onQueue('default');
            Log::info("IMPORT JOB: Dispatched UpdateUserHierarchy job.");

            event(new UserImportCompleted($this->filePath));

        } catch (ValidationException $e) {
            Log::error("IMPORT JOB: Validation failed for {$this->filePath}. The import was stopped.");
            $failures = $e->failures();
            foreach ($failures as $failure) {
                $errorString = implode(', ', $failure->errors());
                Log::error(" - Row: {$failure->row()}, Errors: [{$errorString}]");
            }
        } catch (\Throwable $e) {
            $this->fail($e);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::emergency("IMPORT JOB FAILED: The import for file {$this->filePath} has failed. Reason: " . $exception->getMessage());
    }
}
