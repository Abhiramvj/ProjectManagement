<?php

namespace App\Actions\TimeLog;

use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class StoreTimeLogsAction
{
    /**
     * @throws ValidationException
     */
    public function execute(array $validatedData): void
    {
        $user = Auth::user();

        $workDate = Carbon::parse($validatedData['work_date']);

        // Create the time log entry
        $user->timeLogs()->create($validatedData);

        // Check if the work date is a weekend (Saturday or Sunday)
        $isWeekend = $workDate->isWeekend();

        // Check if the work date is a holiday in the holidays table
        $isHoliday = Holiday::whereDate('date', $workDate->toDateString())->exists();

        // If work date is weekend or holiday, calculate comp off entitlement
        if ($isWeekend || $isHoliday) {
            $hoursWorked = $validatedData['hours_worked'] ?? 0;

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
