<?php

namespace App\Actions\Role;

use Spatie\Permission\Models\Role;

class StoreRole
{
    public function handle(array $data)
    {
        $role = Role::create(['name' => $data['name']]);
        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }
    }
}
