<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class UserHierarchyController extends Controller
{
    public function index()
    {
        $allUsers = User::with('teams')->withCount([
            'tasks',
            'tasks as tasks_completed_count' => function ($query) {
                $query->where('status', 'completed');
            },
        ])->get();

        $managerIds = $allUsers->whereNotNull('parent_id')->pluck('parent_id')->unique()->all();
        $reportingNodes = $this->formatForCompactChart($allUsers, $managerIds);
        $designationBasedNodes = $this->generateDesignationBasedNodes($allUsers);

        return Inertia::render('Hierarchy/CompanyHierarchy', [
            'reportingNodes' => $reportingNodes,
            'designationBasedNodes' => $designationBasedNodes,
        ]);
    }

    private function formatForCompactChart(Collection $users, array $managerIds): array
    {
        $loggedInUser = Auth::user();

        return $users->map(function ($user) use ($managerIds, $loggedInUser) {
            $tags = [];
            if (in_array($user->id, $managerIds)) {
                $tags[] = 'manager';
            }
            if ($user->id === $loggedInUser->id) {
                $tags[] = 'is-logged-in-user';
            }

            $teamName = $user->teams->first()->name ?? $user->designation ?? 'Unassigned';
            $color = $this->generateColorForText($teamName);

            // --- UPDATED PERMISSION LOGIC ---
            $canViewPerformance = $loggedInUser->hasRole('admin') ||    // Is admin?
                                  $loggedInUser->id === $user->parent_id || // Is their manager?
                                  $loggedInUser->id === $user->id;         // Is it their own node?

            $performanceSummary = null;
            if ($canViewPerformance) {
                $performanceSummary = [
                    'tasks_total' => $user->tasks_count,
                    'tasks_completed' => $user->tasks_completed_count,
                ];
            }

            return [
                'id' => $user->id, 'pid' => $user->parent_id, 'name' => $user->name,
                'title' => $user->designation,
                'image' => $user->avatar_url ?? ($user->image ? Storage::url($user->image) : 'https://ui-avatars.com/api/?background=random&name='.urlencode($user->name)),
                'color' => $color, 'tags' => $tags, 'employee_id' => $user->employee_id, 'email' => $user->email,
                'hire_date' => $user->hire_date, 'total_experience' => $user->total_experience,
                'canViewPerformance' => $canViewPerformance, 'performance_summary' => $performanceSummary,
            ];
        })->all();
    }

    private function generateDesignationBasedNodes(Collection $users): array
    {
        $nodes = [];
        $createdDesignationGroups = [];
        $allowedUserIds = $users->pluck('id')->all();
        $loggedInUser = Auth::user();

        foreach ($users as $user) {
            // --- UPDATED PERMISSION LOGIC ---
            $canViewPerformance = $loggedInUser->hasRole('admin') ||    // Is admin?
                                  $loggedInUser->id === $user->parent_id || // Is their manager?
                                  $loggedInUser->id === $user->id;         // Is it their own node?

            $performanceSummary = null;
            if ($canViewPerformance) {
                $performanceSummary = [
                    'tasks_total' => $user->tasks_count,
                    'tasks_completed' => $user->tasks_completed_count,
                ];
            }
            $teamName = $user->teams->first()->name ?? $user->designation ?? 'Unassigned';
            $color = $this->generateColorForText($teamName);
            $imageUrl = $user->avatar_url ?? ($user->image ? Storage::url($user->image) : 'https://ui-avatars.com/api/?name='.urlencode($user->name));

            if (is_null($user->parent_id) || ! in_array($user->parent_id, $allowedUserIds)) {
                $nodes[] = [
                    'id' => $user->id, 'pid' => null, 'name' => $user->name, 'title' => $user->designation, 'image' => $imageUrl,
                    'color' => $color,
                    'tags' => ['employee-node', $user->id === $loggedInUser->id ? 'is-logged-in-user' : ''],
                    'canViewPerformance' => $canViewPerformance, 'performance_summary' => $performanceSummary,
                ];

                continue;
            }

            $directParentId = $user->parent_id;
            $designation = $user->designation ?? 'Unassigned';

            if (! isset($createdDesignationGroups[$directParentId][$designation])) {
                $groupNodeId = 'group_'.$directParentId.'_'.str_replace(' ', '_', $designation);
                $nodes[] = [
                    'id' => $groupNodeId, 'pid' => $directParentId, 'name' => $designation,
                    'title' => 'Designation Group', 'tags' => ['role-category'],
                    'color' => $this->generateColorForText($designation),
                ];
                $createdDesignationGroups[$directParentId][$designation] = true;
            }

            $groupNodeId = 'group_'.$directParentId.'_'.str_replace(' ', '_', $designation);

            $nodes[] = [
                'id' => $user->id, 'pid' => $groupNodeId, 'name' => $user->name, 'title' => $user->designation, 'image' => $imageUrl,
                'color' => $color,
                'tags' => ['employee-node', $user->id === $loggedInUser->id ? 'is-logged-in-user' : ''],
                'canViewPerformance' => $canViewPerformance, 'performance_summary' => $performanceSummary,
            ];
        }

        return $nodes;
    }

    private function generateColorForText(string $text): string
    {
        $hash = crc32($text);
        $hue = $hash % 360;
        $saturation = ($hash % 20) + 65;
        $lightness = ($hash % 10) + 40;

        return "hsl({$hue}, {$saturation}%, {$lightness}%)";
    }
}
