<?php

namespace App\Http\Controllers;

use App\Actions\FeedbackAndIdea\DestroyFeedbackIdeaAction;
use App\Actions\FeedbackAndIdea\MarkInactiveFeedbackIdeaAction;
use App\Actions\FeedbackAndIdea\StoreFeedbackIdeaAction;
use App\Actions\FeedbackAndIdea\ToggleFeedbackIdeaAction;
use App\Actions\FeedbackAndIdea\UpdateFeedbackIdeaAction;
use App\Filters\FeedbackIdeaFilter;
use App\Models\FeedbackIdea;
use App\Models\User;
use Illuminate\Http\Request;

class FeedbackIdeaController extends Controller
{
    // Employee view
    public function indexEmployee(Request $request)
    {
        $query = FeedbackIdea::where('type', $request->type);

        $items = (new FeedbackIdeaFilter($request))->apply($query)->get();

        return inertia('FeedbackAndIdea/EmployeeFeedbackIdeaPage', [
            'items' => $items,
            'type' => $request->type,
        ]);
    }

    // Admin view
    public function indexAdmin($type, Request $request)
    {
        $query = FeedbackIdea::where('type', $type)->with('user:id,name');

        $items = (new FeedbackIdeaFilter($request))->apply($query)->get();
        $employees = User::select('id', 'name')->orderBy('name')->get();

        return inertia('FeedbackAndIdea/AdminFeedbackIdeaPage', [
            'items' => $items,
            'type' => $type,
            'employees' => $employees,
        ]);
    }

    // Store
    public function store(Request $request, $type, StoreFeedbackIdeaAction $action)
    {
        $request->validate(['description' => 'required|string']);

        $action->execute($type, $request->description);

        return redirect()->back()->with('success', ucfirst($type).' submitted successfully.');
    }

    // Edit page
    public function edit($id)
    {
        $item = FeedbackIdea::findOrFail($id);

        if (auth()->id() !== $item->user_id) {
            abort(403);
        }

        return inertia('FeedbackAndIdea/EditFeedbackIdeaPage', [
            'item' => $item,
        ]);
    }

    // Update
    public function update(Request $request, $id, UpdateFeedbackIdeaAction $action)
    {
        $request->validate(['description' => 'required|string|max:5000']);

        $action->execute($id, $request->description);

        return back()->with('success', 'Updated successfully.');
    }

    // Delete
    public function destroy($id, DestroyFeedbackIdeaAction $action)
    {
        $item = $action->execute($id);

        $route = $item->type === 'feedback' ? 'feedback.index' : 'idea.index';

        return redirect()->route($route)->with('success', 'Submission deleted.');
    }

    // Toggle active/inactive
    public function toggle($id, ToggleFeedbackIdeaAction $action)
    {
        $item = $action->execute($id);

        return response()->json([
            'item' => $item,
        ]);
    }

    // Mark inactive (admin/HR only)
    // public function markInactive($id, MarkInactiveFeedbackIdeaAction $action)
    // {
    //     $action->execute($id);

    //     return redirect()-back()->with('success', 'Submission marked as inactive.');
    // }
}
