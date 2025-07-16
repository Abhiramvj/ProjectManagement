<?php

namespace App\Actions\Role;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class GetRoles
{
    public function handle()
    {
        return [
            'roles' => Role::with('permissions:id,name')->get()->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name'),
            ]),
            'allPermissions' => Permission::all()->pluck('name'),
        ];
    }
}
