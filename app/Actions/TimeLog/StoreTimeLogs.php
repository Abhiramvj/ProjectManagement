<?php

namespace App\Actions\TimeLog;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class StoreTimeLogs
{
    /**
     * @throws ValidationException
     */
    public function handle(array $validatedData): void
    {
        $user = Auth::user();

        $workDate = Carbon::parse($validatedData['work_date']);

        // Create the time log entry
        $user->timeLogs()->create($validatedData);

        // Check if the work date is a weekend (Saturday or Sunday)
        if ($workDate->isWeekend()) {
            $hoursWorked = $validatedData['hours_worked'] ?? 0;

            // Calculate comp off entitlement based on hours worked
            $compOffToAdd = 0;

            if ($hoursWorked >= 7) {
                $compOffToAdd = 1; // Full day comp off
            } elseif ($hoursWorked >= 4) {
                $compOffToAdd = 0.5; // Half day comp off
            }
            // Else less than 4 hours, no comp off (adjust if needed)

            if ($compOffToAdd > 0) {
                $user->increment('comp_off_balance', $compOffToAdd);
            }
        }
    }
}
