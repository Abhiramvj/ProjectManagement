<?php

namespace App\Actions\User;

use App\Jobs\ProcessUserImport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;

class ImportUsersAction
{
    public function execute($file)
    {
        try {
            // Store the file in storage/app/imports
            $path = $file->store('imports');

            // Dispatch background job
            ProcessUserImport::dispatch($path);

            return Redirect::back()->with('success', 'File uploaded! The users are being imported in the background.');
        } catch (\Throwable $e) {
            Log::critical('Failed to store file or dispatch import job: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return Redirect::back()->with('error', 'Could not start the import process. Please contact support.');
        }
    }
}
