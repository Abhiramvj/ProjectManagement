<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class UserHierarchyController extends Controller
{
    /**
     * Display the company reporting and leave flow hierarchies.
     */
    public function index()
    {
        $user = Auth::user();
        $user->load('roles');

        $reportingHierarchy = [];
        $leaveFlowHierarchy = [];
        $directManager = null;

        // ----- Leave Flow Hierarchy (optional: see note below) -----
        $hrUsers = User::role('hr')->get();
        if ($hrUsers->isNotEmpty()) {
            if ($user->hasRole(['admin', 'hr'])) {
                // HR/admin see all employees in HR's monitoring path:
                foreach ($hrUsers as $hr) {
                    $hr->children_recursive = User::with('childrenRecursive')
                        ->whereNull('parent_id')
                        ->whereDoesntHave('roles', function ($q) {
                            $q->whereIn('name', ['admin', 'hr']);
                        })
                        ->get();
                }
            } else {
                // Employee: only self in flow under HR
                foreach ($hrUsers as $hr) {
                    $hr->children_recursive = [$user];
                }
            }
            $leaveFlowHierarchy = $hrUsers;
        }

        // ----- Reporting Hierarchy -----
        if ($user->hasRole(['admin', 'hr'])) {
            // Admin/HR: show full company structure (admin as root, all descendants)
            $reportingHierarchy = User::with('childrenRecursive')
                ->whereNull('parent_id')
                ->get();
        } else {
            // Employee: see their manager ("team lead"/PM), and peers
            $parentId = $user->parent_id;
            if ($parentId) {
                // Team lead/manager & their subtree
                $reportingHierarchy = User::with('childrenRecursive')
                    ->where('id', $parentId)
                    ->get();
                $directManager = $reportingHierarchy->first();
            } else {
                // No parent? Just self.
                $reportingHierarchy = User::with('childrenRecursive')
                    ->where('id', $user->id)
                    ->get();
            }
        }

        return Inertia::render('Hierarchy/CompanyHierarchy', [
            'hierarchyNodes' => $reportingHierarchy,
            'leaveFlowNodes' => $leaveFlowHierarchy,
            'directManager' => $directManager,
        ]);
    }
}
