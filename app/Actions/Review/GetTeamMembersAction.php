<?php

namespace App\Actions\Review;

use App\Models\User;
use Illuminate\Support\Collection;

class GetTeamMembersAction
{
    public function execute(Collection $teamMemberIds): Collection
    {
        return User::whereIn('id', $teamMemberIds)
            ->select('id', 'name', 'email', 'designation')
            ->get();
    }
}
