<?php

namespace App\Actions\User;

use App\Models\Team;
use Spatie\Permission\Models\Role;

class CreateUsers
{
    public function handle()
    {
        return [
            'roles' => Role::all()->pluck('name'),
            'teams' => Team::select('id', 'name')->get(),
        ];
    }
}
