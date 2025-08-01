<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // This schedule will run your 'app:add-monthly-leave' command
        // on the 1st day of every month at midnight.
        $schedule->command('app:add-monthly-leave')->monthlyOn(1, '00:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        // This line automatically discovers and registers all command files
        // located in the app/Console/Commands directory.
        $this->load(__DIR__.'/Commands');

        // You do not need to add your commands here manually.
        // As long as the command file exists in the correct directory,
        // this line will handle it.
    }
}
