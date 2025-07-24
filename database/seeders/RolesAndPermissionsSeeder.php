<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions
        $permissions = [
            'log working hours', 'apply for leave', 'assign tasks', 'view team progress',
            'assign projects', 'view all projects progress', 'view all working hours',
            'manage leave applications', 'manage employees', 'manage roles', 'view leaves'
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
        // Safeguard: Ensure team-lead cannot manage leave applications
        $teamLeadRole->revokePermissionTo('manage leave applications');

        $pmRole = Role::firstOrCreate(['name' => 'project-manager']);
        $pmRole->givePermissionTo(['assign projects', 'view all projects progress']);

        $hrRole = Role::firstOrCreate(['name' => 'hr']);
        $hrRole->givePermissionTo([
            'view all working hours', 'manage leave applications', 'manage employees',
            'manage roles', 'apply for leave', 'view leaves',
        ]);

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        // Create Users with Designations (Order matters for parent_id)

        // 1. Admin (root)
        $adminUser = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'designation' => 'System Administrator',
        ]);
        $adminUser->assignRole($adminRole);

        // 2. HR - reports to Admin
        $hrUser = User::factory()->create([
            'name'      => 'HR Manager',
            'email'     => 'hr@example.com',
            'designation' => 'Human Resources',
            'parent_id' => $adminUser->id,
        ]);
        $hrUser->assignRole($hrRole);

        // 3. Project Manager - reports to Admin
        $pmUser = User::factory()->create([
            'name'      => 'PM User',
            'email'     => 'pm@example.com',
            'designation' => 'Project Manager',
            'parent_id' => $adminUser->id,
        ]);
        $pmUser->assignRole($pmRole);

        // 4. Team Lead - reports to PM
        $leadUser = User::factory()->create([
            'name'      => 'Team Lead User',
            'email'     => 'lead@example.com',
            'designation' => 'Lead Developer',
            'parent_id' => $pmUser->id,
        ]);
        $leadUser->assignRole($teamLeadRole);

        // 5. Employee - reports to Team Lead
        $employeeUser = User::factory()->create([
            'name'      => 'Employee User',
            'email'     => 'employee@example.com',
            'designation' => 'Software Engineer',
            'parent_id' => $leadUser->id,
        ]);
        $employeeUser->assignRole($employeeRole);

        // Create another employee for the team
        $employeeUser2 = User::factory()->create([
            'name'      => 'Jane Doe',
            'email'     => 'jane@example.com',
            'designation' => 'Frontend Developer',
            'parent_id' => $leadUser->id,
        ]);
        $employeeUser2->assignRole($employeeRole);


        // Create Team and assign members
        $devTeam = Team::firstOrCreate([
            'name' => 'Core Development Team',
            'team_lead_id' => $leadUser->id
        ]);
        $devTeam->members()->sync([$leadUser->id, $employeeUser->id, $employeeUser2->id]);
    }
}