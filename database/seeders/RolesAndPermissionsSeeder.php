<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\Team;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
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

        // Team Lead Role
        $teamLeadRole = Role::create(['name' => 'team-lead']);
        $teamLeadRole->givePermissionTo([
            'assign tasks',
            'view team progress',
            'log working hours',
            'apply for leave',
            'view leaves', // <-- GIVE PERMISSION TO TEAM LEADS
        ]);

        // Project Manager Role
        $pmRole = Role::create(['name' => 'project-manager']);
        $pmRole->givePermissionTo(['assign projects', 'view all projects progress']);

        // HR Role
        $hrRole = Role::create(['name' => 'hr']);
        $hrRole->givePermissionTo([
            'view all working hours', 'manage leave applications', 'manage employees',
            'manage roles', 'apply for leave',
            'view leaves', // <-- GIVE PERMISSION TO HR
        ]);

        // Admin Role
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all()); // Admin automatically gets 'view leaves'

        // Create Demo Users and Team
        $leadUser = User::factory()->create([ 'name' => 'Team Lead User', 'email' => 'lead@example.com', ]);
        $leadUser->assignRole($teamLeadRole);

        $employeeUser = User::factory()->create([ 'name' => 'Employee User', 'email' => 'employee@example.com', ]);
        $employeeUser->assignRole($employeeRole);

        $devTeam = Team::create([ 'name' => 'Core Development Team', 'team_lead_id' => $leadUser->id ]);
        $devTeam->members()->attach([$leadUser->id, $employeeUser->id]);

        User::factory()->create(['name' => 'Admin User', 'email' => 'admin@example.com'])->assignRole($adminRole);
        User::factory()->create(['name' => 'HR User', 'email' => 'hr@example.com'])->assignRole($hrRole);
        User::factory()->create(['name' => 'PM User', 'email' => 'pm@example.com'])->assignRole($pmRole);
    }
}
