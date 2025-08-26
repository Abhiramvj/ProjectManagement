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
     */
    public function run(): void
    {
        // Force a fresh database connection to prevent state issues.
        app('db')->reconnect();

        // Reset cached roles and permissions.
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // --- Create Permissions ---
        $permissions = [
            'log working hours',
            'apply for leave',
            'assign tasks',
            'view team progress',
            'assign projects',
            'view all projects progress',
            'view all working hours',
            'manage leave applications',
            'manage employees',
            'manage roles',
            'view leaves',
            'manage announcements',
            'view mail logs',

        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // --- Create Roles and Assign Permissions ---

        // Employee Role
        $employeeRole = Role::firstOrCreate(['name' => 'employee']);
        $employeeRole->syncPermissions(['log working hours', 'apply for leave']);

        // Team Lead Role
        $teamLeadRole = Role::firstOrCreate(['name' => 'team-lead']);
        $teamLeadRole->syncPermissions([
            'assign tasks', 'view team progress', 'log working hours', 'apply for leave', 'view leaves', 'manage leave applications',
        ]);

        // Project Manager Role
        $pmRole = Role::firstOrCreate(['name' => 'project-manager']);
        $pmRole->syncPermissions(['assign projects', 'view all projects progress']);

        // HR Role
        $hrRole = Role::firstOrCreate(['name' => 'hr']);
        $hrRole->syncPermissions([
            'view all working hours',
            'manage leave applications',
            'manage employees',
            'manage roles',
            'apply for leave',
            'view leaves',
            'manage announcements',
            'view mail logs',

        ]);

        // Admin Role
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        $adminRole->syncPermissions(Permission::all());

    }
}
