<?php

namespace App\Http\Controllers;

use App\Models\FeedbackIdea;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeedbackIdeaController extends Controller
{
    public function indexEmployee(Request $request)
    {
        $query = FeedbackIdea::where('type', $request->type)
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc');

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        } elseif ($request->date_filter === 'today') {
            $query->whereDate('created_at', now()->toDateString());
        } elseif ($request->date_filter === 'week') {
            $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($request->date_filter === 'month') {
            $query->whereMonth('created_at', now()->month);
        }

        $items = $query->get();

        return inertia('FeedbackAndIdea/EmployeeFeedbackIdeaPage', [
            'items' => $items,
            'type' => $request->type,
        ]);
    }

    public function indexAdmin($type, Request $request)
    {
        $query = FeedbackIdea::where('type', $type)->with('user:id,name');

        if ($request->employee_id) {
            $query->where('user_id', $request->employee_id);
        }

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        } elseif ($request->date_filter === 'today') {
            $query->whereDate('created_at', now()->toDateString());
        } elseif ($request->date_filter === 'week') {
            $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($request->date_filter === 'month') {
            $query->whereMonth('created_at', now()->month);
        }

        $items = $query->orderBy('created_at', 'desc')->get();
        $employees = User::select('id', 'name')->orderBy('name')->get();

        return inertia('FeedbackAndIdea/AdminFeedbackIdeaPage', [
            'items' => $items,
            'type' => $type,
            'employees' => $employees,
        ]);
    }

    public function store(Request $request, $type)
    {
        $request->validate([
            'description' => 'required|string',
        ]);

        FeedbackIdea::create([
            'user_id' => Auth::id(),
            'type' => $type,
            'description' => $request->description,
        ]);

        return redirect()->back()->with('success', ucfirst($type).' submitted successfully.');
    }

    public function markInactive($id)
    {
        $item = FeedbackIdea::findOrFail($id);

        // Only admin or HR can mark inactive
        if (! auth()->user()->hasRole('admin') && ! auth()->user()->hasRole('hr')) {
            abort(403);
        }

        $item->update(['is_active' => false]);

        return redirect()->back()->with('success', 'Submission marked as inactive.');
    }

    public function edit($id)
    {
        $item = FeedbackIdea::findOrFail($id);

        // Optional: Add authorization check
        if (auth()->id() !== $item->user_id) {
            abort(403);
        }

        return inertia('FeedbackAndIdea/EditFeedbackIdeaPage', [
            'item' => $item,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'description' => 'required|string|max:5000',
        ]);

        $item = FeedbackIdea::findOrFail($id);
        $item->description = $request->description;
        $item->save();

        return back()->with('success', 'Updated successfully.');
    }

    public function destroy($id)
    {
        $item = FeedbackIdea::findOrFail($id);

        if (auth()->id() !== $item->user_id) {
            abort(403);
        }

        $item->delete();

        $route = $item->type === 'feedback' ? 'feedback.index' : 'idea.index';

        return redirect()->route($route)->with('success', 'Submission deleted.');
    }

    public function toggle($id)
    {
        $item = FeedbackIdea::findOrFail($id);
        $item->is_active = ! $item->is_active;
        $item->save();

        return back();
    }
}
