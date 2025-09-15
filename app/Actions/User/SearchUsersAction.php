<?php

namespace App\Actions\User;

use App\Models\User;

class SearchUsersAction
{
    public function execute(string $query)
    {
        if (strlen($query) < 2) {
            return collect(); // return empty collection
        }

        return User::where('name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->select('id', 'name', 'email')
            ->limit(10)
            ->get();
    }
}
