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
}
