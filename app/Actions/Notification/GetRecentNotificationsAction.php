<?php

namespace App\Actions\Notification;

use App\Models\Notification;
use App\Models\User;

class GetRecentNotificationsAction
{
    /**
     * Execute the action to fetch recent notifications based on user role.
     *
     * @return \Illuminate\Support\Collection
     */
    public function execute(User $user, int $limit = 10)
    {
        $query = Notification::query();

        // Use the trait scope method
        $query->queryByUserRole($user);

        return $query->with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
