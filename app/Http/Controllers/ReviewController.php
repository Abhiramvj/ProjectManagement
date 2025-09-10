<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\ReviewCriteria;
use App\Models\User;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Display a listing of the reviews with related data for form.
     */
public function index()
{
    $currentUser = auth()->user();

    if ($currentUser->hasRole('team-lead')) {  // Use hasRole with correct role name from Spatie
        $users = User::where('parent_id', $currentUser->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $userIds = $users->pluck('id')->toArray();

        $reviews = Review::with(['user', 'criteria'])
            ->whereIn('user_id', $userIds)
            ->latest()
            ->paginate(20);

    } else {
        $users = collect([$currentUser]);

        $reviews = Review::with(['user', 'criteria'])
            ->where('user_id', $currentUser->id)
            ->latest()
            ->paginate(20);
    }

    $criterias = ReviewCriteria::orderBy('name')->get(['id', 'name']);

    return inertia('Reviews/CreateReview', compact('users', 'criterias', 'reviews'));
}


    /**
     * Store a newly created review in storage.
     */
  public function store(Request $request)
{
    $currentUser = auth()->user();

    if ($currentUser->hasRole('team-lead')) {
        $employeeIds = User::where('parent_id', $currentUser->id)->pluck('id')->toArray();

        $data = $request->validate([
            'user_id' => ['required', 'in:' . implode(',', $employeeIds)],
            'criteria_id' => ['required', 'exists:review_criterias,id'],
            'month' => ['required', 'string', 'max:20'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'score' => ['required', 'numeric', 'min:0', 'max:100'],
            'rating' => ['nullable', 'string', 'max:255'],
        ]);
    } else {
        $data = $request->validate([
            'user_id' => ['required', 'in:' . $currentUser->id],
            'criteria_id' => ['required', 'exists:review_criterias,id'],
            'month' => ['required', 'string', 'max:20'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'score' => ['required', 'numeric', 'min:0', 'max:100'],
            'rating' => ['nullable', 'string', 'max:255'],
        ]);
    }

    // Add reviewer_id explicitly before creating the review
    $data['reviewer_id'] = $currentUser->id;

    Review::create($data);

    return back()->with('success', 'Review added successfully.');
}

}
