<?php

namespace App\Helpers;

class ColorHelper
{
    public static function getLeaveColor(string $leaveType): string
    {
        $colors = [
            'annual' => '#3B82F6', // Blue
            'sick' => '#EF4444', // Red
            'personal' => '#F59E0B', // Amber
            'emergency' => '#DC2626', // Dark Red
            'maternity' => '#EC4899', // Pink
            'paternity' => '#8B5CF6', // Purple
        ];

        return $colors[$leaveType] ?? '#6B7280'; // Default gray
    }
}
