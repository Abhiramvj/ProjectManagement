<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class UserHierarchyController extends Controller
{
    /**
     * Display the company and leave hierarchy views.
     */
    public function index()
    {
        $user = Auth::user();
        $user->load('roles');

        $reportingHierarchy = [];
        $leaveFlowHierarchy = [];
        $directManager = null; // Initialize a variable to hold the direct manager

        // --- 1. Prepare Leave Approval Flowchart (No changes here) ---
        $hrUsers = User::role('hr')->get();
        if ($hrUsers->isNotEmpty()) {
            if ($user->hasRole(['admin', 'hr'])) {
                $employees = User::whereDoesntHave('roles', function ($query) {
                    $query->whereIn('name', ['admin', 'hr']);
                })->get(['id', 'name', 'email']);
                foreach ($hrUsers as $hr) {
                    $hr->children_recursive = $employees;
                }
            } else {
                foreach ($hrUsers as $hr) {
                    $hr->children_recursive = [$user];
                }
            }
            $leaveFlowHierarchy = $hrUsers;
        }

        // --- 2. Prepare Standard Reporting Hierarchy (THIS SECTION IS UPDATED) ---
        if ($user->hasRole(['admin', 'hr'])) {
            // Admins and HR see the full chart, no changes here.
            $reportingHierarchy = User::with('childrenRecursive')
                ->whereNull('parent_id')
                ->get();
        } else {
            // ** NEW LOGIC FOR EMPLOYEES **
            // Find the user's parent ID to determine their team.
            $parentId = $user->parent_id;

            if ($parentId) {
                // If the user has a parent, fetch the PARENT and all their children (the user's team).
                // This makes the Team Lead the root of the hierarchy view.
                $reportingHierarchy = User::with('childrenRecursive')
                    ->where('id', $parentId)
                    ->get();
                // We can explicitly pass the manager for a cleaner display in the UI.
                $directManager = $reportingHierarchy->first();
            } else {
                // Fallback for users with no parent (e.g., a manager who is not an admin).
                // They will only see themselves and their own direct reports.
                $reportingHierarchy = User::with('childrenRecursive')
                    ->where('id', $user->id)
                    ->get();
            }
        }

        // --- 3. Render the View ---
        return Inertia::render('Hierarchy/CompanyHierarchy', [
            'hierarchyNodes'   => $reportingHierarchy,
            'leaveFlowNodes'   => $leaveFlowHierarchy,
            'directManager'    => $directManager, // Pass the direct manager explicitly
        ]);
    }
}
