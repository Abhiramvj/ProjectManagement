<?php

namespace App\Actions\Role;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class GetRolesEdit
{
    public function handle(Role $role)
    {
        return [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name'),
            ],
            'allPermissions' => Permission::all()->pluck('name'),
        ];
    }
}
