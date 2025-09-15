<?php

namespace App\Traits\Scopes;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait QueryByUserRole
{
    /**
     * Apply query scope based on user role.
     */
    public function scopeQueryByUserRole(Builder $query, User $user): void
    {
        if ($user->hasAnyRole(['admin', 'hr'])) {
            // Admin/HR: can see all notifications of employees, leads, PMs
            $query->whereHas('user.roles', function ($q) {
                $q->whereIn('name', ['employee', 'team-lead', 'project-manager']);
            });
        } elseif ($user->hasRole('team-lead')) {
            $ledTeams = $user->ledTeams()->with('members:id')->get();
            $memberIds = $ledTeams->flatMap(fn ($team) => $team->members->pluck('id'))->unique();

            $query->where(function ($q) use ($user, $memberIds) {
                $q->whereIn('notifiable_id', $memberIds)
                    ->orWhere(function ($q2) use ($user) {
                        $q2->where('notifiable_id', $user->id)
                            ->whereIn('data->type', ['leave_approved', 'leave_rejected']);
                    });
            });
        } else {
            // Regular employee: only own notifications
            $query->where('notifiable_id', $user->id)
                ->where('notifiable_type', User::class);
        }
    }
}
