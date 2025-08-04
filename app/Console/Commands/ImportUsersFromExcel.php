<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\UsersImport;

class ImportUsersFromExcel extends Command
{
    protected $signature = 'import:users {file}';
    protected $description = 'Import users from an Excel file';

    public function handle()
    {
        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error("File not found: $file");
            return 1;
        }

        try {
            Excel::import(new UsersImport, $file);
            $this->info('✅ Users imported successfully.');
        } catch (\Exception $e) {
            $this->error("❌ Import failed: " . $e->getMessage());
        }

        return 0;
    }
}
