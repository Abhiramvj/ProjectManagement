<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\UsersImport;

class ImportUsers extends Command
{
    protected $signature = 'import:users {file}';
    protected $description = 'Import users from an Excel file';

    public function handle()
    {
        $filePath = $this->argument('file');

        Excel::import(new UsersImport, $filePath);

        $this->info('Users imported successfully!');
    }
}
