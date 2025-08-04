<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class UserHierarchyController extends Controller
{
    /**
     * Display the company hierarchy page.
     */
    public function index()
    {
        $currentUser = Auth::user();
        $usersToShow = new Collection();

        // --- 1. DETERMINE THE SCOPE OF USERS TO DISPLAY ---
        if ($currentUser->hasRole('admin')) {
            // Admins see everyone.
            $usersToShow = User::all();
        } else {
            // Non-admins see a "local view": their manager, peers, and direct reports.
            $userIdsToShow = [$currentUser->id];

            // Get manager's ID
            if ($currentUser->parent_id) {
                $userIdsToShow[] = $currentUser->parent_id;
            }

            // Get peer IDs (others reporting to the same manager)
            if ($currentUser->parent_id) {
                $peerIds = User::where('parent_id', $currentUser->parent_id)
                                ->where('id', '!=', $currentUser->id) // Exclude self
                                ->pluck('id')->all();
                $userIdsToShow = array_merge($userIdsToShow, $peerIds);
            }

            // Get direct report IDs
            $reportIds = User::where('parent_id', $currentUser->id)->pluck('id')->all();
            $userIdsToShow = array_merge($userIdsToShow, $reportIds);

            // Fetch the final user models from the database.
            $uniqueIds = array_unique($userIdsToShow);
            $usersToShow = User::whereIn('id', $uniqueIds)->get();
        }


        // --- 2. GENERATE NODE DATA FOR BOTH CHARTS ---

        // The "Reporting Structure" chart always uses the direct parent_id relationship.
        $reportingNodes = $this->formatUsersForBalkanGraph($usersToShow);

        // The "Designation Hierarchy" chart uses the more complex grouping logic.
        $designationBasedNodes = $this->generateDesignationBasedNodes($usersToShow);


        // --- 3. RENDER THE VIEW ---
        return Inertia::render('Hierarchy/CompanyHierarchy', [
            'reportingNodes' => $reportingNodes,
            'designationBasedNodes' => $designationBasedNodes,
        ]);
    }

    /**
     * Generates a hybrid hierarchy from a GIVEN collection of users.
     * This makes the function reusable for both admin and non-admin views.
     */
    private function generateDesignationBasedNodes(Collection $users): array
    {
        $nodes = [];
        // This array will keep track of which designation groups we have already created.
        $createdDesignationGroups = [];
        // We need to know the IDs of the users we are allowed to show.
        $allowedUserIds = $users->pluck('id')->all();

        foreach ($users as $user) {
            
            // If the user has no parent OR their parent is NOT in the allowed list,
            // they are a top-level node for this specific view.
            if (is_null($user->parent_id) || !in_array($user->parent_id, $allowedUserIds)) {
                $imageUrl = $user->avatar_url ?? ($user->image ? Storage::url($user->image) : 'https://ui-avatars.com/api/?name=' . urlencode($user->name));
                $nodes[] = [
                    'id'    => $user->id,
                    'pid'   => null, // Make them a root of the current chart view.
                    'name'  => $user->name,
                    'title' => $user->designation,
                    'image' => $imageUrl,
                    'tags'  => ['ceo', $user->id === Auth::id() ? 'is-logged-in-user' : ''],
                ];
                continue;
            }

            // For all other users, apply the grouping logic.
            $directParentId = $user->parent_id;

            if (!isset($createdDesignationGroups[$directParentId][$user->designation])) {
                $groupNodeId = 'group_' . $directParentId . '_' . str_replace(' ', '_', $user->designation);
                $nodes[] = [
                    'id'    => $groupNodeId,
                    'pid'   => $directParentId,
                    'name'  => $user->designation,
                    'title' => 'Designation',
                    'image' => 'https://cdn-icons-png.flaticon.com/512/3715/3715202.png',
                    'tags'  => ['role-category'],
                ];
                $createdDesignationGroups[$directParentId][$user->designation] = true;
            }

            $groupNodeId = 'group_' . $directParentId . '_' . str_replace(' ', '_', $user->designation);
            $imageUrl = $user->avatar_url ?? ($user->image ? Storage::url($user->image) : 'https://ui-avatars.com/api/?name=' . urlencode($user->name));
            
            $nodes[] = [
                'id'    => $user->id,
                'pid'   => $groupNodeId,
                'name'  => $user->name,
                'title' => $user->designation,
                'image' => $imageUrl,
                'tags'  => ['employee-node', $user->id === Auth::id() ? 'is-logged-in-user' : ''],
            ];
        }

        return $nodes;
    }

    /**
     * Formats a collection of User models for the direct reporting chart.
     */
    private function formatUsersForBalkanGraph(Collection $users)
    {
        // We need to know the IDs of the users we are allowed to show
        // to correctly determine the root of the current view.
        $allowedUserIds = $users->pluck('id')->all();

        return $users->map(function ($user) use ($allowedUserIds) {
            $title = $user->designation ?? 'Employee';
            $tags = ['employee-node'];

            if ($user->id === Auth::id()) {
                $tags[] = 'is-logged-in-user';
            }

            // A user is the top of the current view if their parent is null
            // OR if their parent is not in the list of users we're showing.
            if (is_null($user->parent_id) || !in_array($user->parent_id, $allowedUserIds)) {
                $tags[] = 'ceo';
            }

            $imageUrl = $user->avatar_url ?? ($user->image ? Storage::url($user->image) : 'https://ui-avatars.com/api/?name=' . urlencode($user->name));

            return [
                'id' => $user->id,
                'pid' => $user->parent_id,
                'name' => $user->name,
                'title' => $title,
                'image' => $imageUrl,
                'tags' => $tags,
            ];
        });
    }
}