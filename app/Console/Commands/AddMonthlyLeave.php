<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class AddMonthlyLeave extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:add-monthly-leave';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $updatedUsers = User::where('leave_balance', '<', 18)->get();

        foreach ($updatedUsers as $user) {
            $user->increment('leave_balance');
        }

        $this->info('Monthly paid leave added to eligible users.');
    }
}
