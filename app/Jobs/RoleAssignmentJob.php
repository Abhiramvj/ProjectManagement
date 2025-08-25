<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class RoleAssignmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $emails;
    protected $rolesInFile;

    public function __construct(array $emails, array $rolesInFile)
    {
        $this->emails = $emails;
        $this->rolesInFile = $rolesInFile;
    }

    public function handle()
    {
        // Load all roles once
        $roles = Role::all()->keyBy(fn($role) => strtolower($role->name));

        // Fetch users for the given emails
        $users = User::whereIn('email', $this->emails)->get()->keyBy('email');

        $assignments = [];
        foreach ($this->emails as $index => $email) {
            $user = $users->get($email);
            $roleName = strtolower(trim($this->rolesInFile[$index] ?? ''));

            if ($user && $roles->has($roleName)) {
                $assignments[] = [
                    'role_id' => $roles->get($roleName)->id,
                    'model_type' => User::class,
                    'model_id' => $user->id,
                ];
            }
        }

        if (!empty($assignments)) {
            // Insert all role assignments at once (avoid duplicates before insert if needed)
            DB::table('model_has_roles')->insert($assignments);
        }
    }
}
