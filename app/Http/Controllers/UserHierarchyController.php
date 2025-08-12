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
        // Fetch users with their teams and task counts efficiently.
        $allUsers = User::with('teams')->withCount([
            'tasks', // total tasks assigned
            'tasks as tasks_completed_count' => function ($query) {
                $query->where('status', 'completed'); // completed tasks
            }
        ])->get();

        $managerIds = $allUsers->whereNotNull('parent_id')->pluck('parent_id')->unique()->all();
        $reportingNodes = $this->formatForCompactChart($allUsers, $managerIds);
        $designationBasedNodes = $this->generateDesignationBasedNodes($allUsers); // This remains for the other tab

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
            if (in_array($user->id, $managerIds)) $tags[] = 'manager';
            if ($user->id === $loggedInUser->id) $tags[] = 'is-logged-in-user';

            $teamName = $user->teams->first()->name ?? $user->designation ?? 'Unassigned';
            $color = $this->generateColorForText($teamName);

            // Permission & Performance Logic
            $canViewPerformance = $loggedInUser->hasRole('admin') || $loggedInUser->id === $user->parent_id;
            
            $performanceSummary = null;
            if ($canViewPerformance) {
                $performanceSummary = [
                    'tasks_total' => $user->tasks_count,
                    'tasks_completed' => $user->tasks_completed_count,
                ];
            }

            return [
                'id'    => $user->id,
                'pid'   => $user->parent_id,
                'name'  => $user->name,
                'title' => $user->designation,
                'image' => $user->avatar_url ?? ($user->image ? Storage::url($user->image) : 'https://ui-avatars.com/api/?background=random&name=' . urlencode($user->name)),
                'color' => $color,
                'tags'  => $tags,
                'employee_id' => $user->employee_id,
                'email' => $user->email,
                'hire_date' => $user->hire_date,
                'total_experience_years' => $user->total_experience_years,
                'canViewPerformance' => $canViewPerformance,
                'performance_summary' => $performanceSummary,
            ];
        })->all();
    }

    private function generateDesignationBasedNodes(Collection $users): array
    {
        // This function for the second tab remains unchanged
        $nodes = [];
        $createdDesignationGroups = [];
        $allowedUserIds = $users->pluck('id')->all(); 

        foreach ($users as $user) {
            if (is_null($user->parent_id) || !in_array($user->parent_id, $allowedUserIds)) {
                $imageUrl = $user->avatar_url ?? ($user->image ? Storage::url($user->image) : 'https://ui-avatars.com/api/?name=' . urlencode($user->name));
                $nodes[] = [
                    'id'    => $user->id, 'pid'   => null, 'name'  => $user->name, 'title' => $user->designation, 'image' => $imageUrl,
                    'tags'  => ['employee-node', $user->id === Auth::id() ? 'is-logged-in-user' : ''],
                ];
                continue;
            }

            $directParentId = $user->parent_id;
            $designation = $user->designation ?? 'Unassigned';

            if (!isset($createdDesignationGroups[$directParentId][$designation])) {
                $groupNodeId = 'group_' . $directParentId . '_' . str_replace(' ', '_', $designation);
                $nodes[] = [
                    'id'    => $groupNodeId, 'pid'   => $directParentId, 'name'  => $designation, 'title' => 'Designation Group',
                    'image' => 'https://cdn-icons-png.flaticon.com/512/3715/3715202.png', 'tags'  => ['role-category'],
                ];
                $createdDesignationGroups[$directParentId][$designation] = true;
            }

            $groupNodeId = 'group_' . $directParentId . '_' . str_replace(' ', '_', $designation);
            $imageUrl = $user->avatar_url ?? ($user->image ? Storage::url($user->image) : 'https://ui-avatars.com/api/?name=' . urlencode($user->name));
            
            $nodes[] = [ 'id'    => $user->id, 'pid'   => $groupNodeId, 'name'  => $user->name, 'title' => $user->designation,
                'image' => $imageUrl, 'tags'  => ['employee-node', $user->id === Auth::id() ? 'is-logged-in-user' : ''],
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