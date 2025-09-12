<?php

namespace App\Actions\Dashboard;

class GetGreetingAction
{
    public function execute()
    {
        $hour = now()->hour;
        $greetingMessage = 'Morning';
        $greetingIcon = '🌤️';
        if ($hour >= 12 && $hour < 17) {
            $greetingMessage = 'Afternoon';
            $greetingIcon = '☀️';
        } elseif ($hour >= 17) {
            $greetingMessage = 'Evening';
            $greetingIcon = '🌙';
        }

        return [
            'message' => $greetingMessage,
            'icon' => $greetingIcon,
            'date' => now()->format('jS F Y'),
        ];
    }
}
