<?php

namespace App\Actions\LeaveApplication;

use App\Models\Holiday;
use Carbon\Carbon;

class LeaveDayCalculator
{
    public function calculate(string $startDate, string $endDate, ?string $startSession, ?string $endSession): float
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        if ($start->gt($end)) {
            throw new \InvalidArgumentException('Start date must be before end date');
        }

        // ✅ normalize sessions
        $startSession = $startSession === '' ? null : $startSession;
        $endSession = $endSession === '' ? null : $endSession;

        $leaveDays = 0;

        if ($start->isSameDay($end)) {
            $isWeekend = in_array($start->dayOfWeekIso, [6, 7]);
            $isHoliday = Holiday::whereDate('date', $start->toDateString())->exists();

            if (! $isWeekend && ! $isHoliday) {
                if ($startSession === 'morning' && $endSession === 'afternoon') {
                    $leaveDays = 1.0;
                } elseif (
                    ($startSession === 'morning' && $endSession === null) ||
                    ($startSession === null && $endSession === 'afternoon') ||
                    ($startSession === 'morning' && $endSession === 'morning') ||
                    ($startSession === 'afternoon' && $endSession === 'afternoon') ||
                    ($startSession === 'afternoon' && $endSession === null)
                ) {
                    $leaveDays = 0.5;
                } else {
                    $leaveDays = 1.0;
                }
            }
        } else {
            $leaveDays = $this->calculateMultiDay($start, $end, $startSession, $endSession);
        }

        return max(0, $leaveDays);
    }

    private function calculateMultiDay($start, $end, ?string $startSession, ?string $endSession): float
    {
        $firstDayValue = 0.0;
        if (! in_array($start->dayOfWeekIso, [6, 7])
            && ! Holiday::whereDate('date', $start->toDateString())->exists()) {
            $firstDayValue = ($startSession === 'afternoon') ? 0.5 : 1.0;
        }

        $lastDayValue = 0.0;
        if (! in_array($end->dayOfWeekIso, [6, 7])
            && ! Holiday::whereDate('date', $end->toDateString())->exists()) {
            $lastDayValue = ($endSession === 'morning') ? 0.5 : 1.0;
        }

        $workingDaysInBetween = 0;
        $currentDay = $start->copy()->addDay();

        while ($currentDay->lt($end)) {
            $isWeekend = in_array($currentDay->dayOfWeekIso, [6, 7]);
            $isHoliday = Holiday::whereDate('date', $currentDay->toDateString())->exists();

            if (! $isWeekend && ! $isHoliday) {
                $workingDaysInBetween++;
            }

            $currentDay->addDay();
        }

        return $firstDayValue + $lastDayValue + $workingDaysInBetween;
    }
}
