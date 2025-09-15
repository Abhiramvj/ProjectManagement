<?php

namespace App\Actions\Notification;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class GetNotificationsAction
{
    public function execute(): Builder
    {
        $user = Auth::user();

        $query = Notification::query();

        // Apply role-based scoping
        $this->scopeQueryByUserRole($query, $user);

        // Exclude self-generated notifications for team-leads
        if ($user->hasRole('team-lead')) {
            $query->where('notifiable_id', '!=', $user->id);
        }

        return $query->with('user:id,name')
            ->orderBy('created_at', 'desc');
    }

    private function scopeQueryByUserRole(Builder $query, User $user): void
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
