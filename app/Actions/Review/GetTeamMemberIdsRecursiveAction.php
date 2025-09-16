<?php

namespace App\Actions\Review;

use App\Models\User;
use Illuminate\Support\Collection;

class GetTeamMemberIdsRecursiveAction
{
    public function execute(int $userId): Collection
    {
        $directMembers = User::where('parent_id', $userId)->pluck('id');

        $allMembers = collect($directMembers);

        foreach ($directMembers as $memberId) {
            $allMembers = $allMembers->merge($this->execute($memberId));
        }

        return $allMembers->unique()->values();
    }
}
