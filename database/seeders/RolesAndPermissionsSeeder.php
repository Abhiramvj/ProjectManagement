<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * This seeder is responsible ONLY for creating roles and permissions.
     * All other data (users, teams, projects) is seeded in other files.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions
        $permissions = [
            'log working hours', 'apply for leave', 'assign tasks', 'view team progress',
            'assign projects', 'view all projects progress', 'view all working hours',
            'manage leave applications', 'manage employees', 'manage roles', 'view leaves',
            'manage announcements', // NEW: Add the permission for announcements
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Roles and Assign Permissions
        $employeeRole = Role::firstOrCreate(['name' => 'employee']);
        $employeeRole->givePermissionTo(['log working hours', 'apply for leave']);

        $teamLeadRole = Role::firstOrCreate(['name' => 'team-lead']);
        $teamLeadRole->givePermissionTo([
            'assign tasks', 'view team progress', 'log working hours', 'apply for leave', 'view leaves',
        ]);

        $pmRole = Role::firstOrCreate(['name' => 'project-manager']);
        $pmRole->givePermissionTo(['assign projects', 'view all projects progress']);

        $hrRole = Role::firstOrCreate(['name' => 'hr']);
        $hrRole->givePermissionTo([
            'view all working hours', 'manage leave applications', 'manage employees',
            'manage roles', 'apply for leave', 'view leaves',
            'manage announcements', // NEW: Give HR the ability to manage announcements
        ]);

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        // The admin role automatically gets the new permission because it uses Permission::all()
        $adminRole->givePermissionTo(Permission::all());
    }
}
