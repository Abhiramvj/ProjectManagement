<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles/permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create All Permissions
        Permission::create(['name' => 'log working hours']);
        Permission::create(['name' => 'apply for leave']);
        Permission::create(['name' => 'assign tasks']);
        Permission::create(['name' => 'view team progress']);
        Permission::create(['name' => 'assign projects']);
        Permission::create(['name' => 'view all projects progress']);
        Permission::create(['name' => 'view all working hours']);
        Permission::create(['name' => 'manage leave applications']);
        Permission::create(['name' => 'manage employees']);
        Permission::create(['name' => 'manage roles']);
        Permission::create(['name' => 'view leaves']); // <-- ADD THIS PERMISSION

        // Employee Role
        $employeeRole = Role::create(['name' => 'employee']);
        $employeeRole->givePermissionTo(['log working hours', 'apply for leave']);

        $teamLeadRole = Role::firstOrCreate(['name' => 'team-lead']);
        $teamLeadRole->givePermissionTo([
            'assign tasks',
            'view team progress',
            'log working hours',
            'apply for leave',
            'view leaves', // <-- GIVE PERMISSION TO TEAM LEADS
        ]);

        $pmRole = Role::firstOrCreate(['name' => 'project-manager']);
        $pmRole->givePermissionTo(['assign projects', 'view all projects progress']);

        $hrRole = Role::firstOrCreate(['name' => 'hr']);
        $hrRole->givePermissionTo([
            'view all working hours', 'manage leave applications', 'manage employees',
            'manage roles', 'apply for leave',
            'view leaves', // <-- GIVE PERMISSION TO HR
        ]);

        // Admin Role
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all()); // Admin automatically gets 'view leaves'

        // Create users (order matters due to parent_id setup)

        // 1. Admin (root)
        $adminUser = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com'
        ]);
        $adminUser->assignRole($adminRole);

        // 2. HR - reports to Admin
        $hrUser = User::factory()->create([
            'name'      => 'HR User',
            'email'     => 'hr@example.com',
            'parent_id' => $adminUser->id
        ]);
        $hrUser->assignRole($hrRole);

        // 3. Project Manager - reports to Admin
        $pmUser = User::factory()->create([
            'name'      => 'PM User',
            'email'     => 'pm@example.com',
            'parent_id' => $adminUser->id
        ]);
        $pmUser->assignRole($pmRole);

        // 4. Team Lead - reports to PM
        $leadUser = User::factory()->create([
            'name'      => 'Team Lead User',
            'email'     => 'lead@example.com',
            'parent_id' => $pmUser->id
        ]);
        $leadUser->assignRole($teamLeadRole);

        // 5. Employee - reports to Team Lead
        $employeeUser = User::factory()->create([
            'name'      => 'Employee User',
            'email'     => 'employee@example.com',
            'parent_id' => $leadUser->id
        ]);
        $employeeUser->assignRole($employeeRole);

        // 6. Create Team and assign members
        $devTeam = Team::firstOrCreate([
            'name' => 'Core Development Team',
            'team_lead_id' => $leadUser->id
        ]);
        $devTeam->members()->syncWithoutDetaching([$leadUser->id, $employeeUser->id]);
    }
}
