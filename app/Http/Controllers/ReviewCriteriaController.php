<?php

namespace App\Http\Controllers;

use App\Models\ReviewCriteria;
use App\Models\ReviewCategory;
use Illuminate\Http\Request;

class ReviewCriteriaController extends Controller
{
    /**
     * Show all criteria with categories
     */
    public function index()
    {
        $categories = ReviewCategory::with('criteria')->get();

        return inertia('Reviews/CriteriaIndex', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a new review criterion
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:review_categories,id',
            'name' => 'required|string|max:255',
            'max_points' => 'required|integer|min:1',
        ]);

        ReviewCriteria::create($validated);

        return back()->with('success', 'Review criteria added successfully!');
    }

    /**
     * Delete a criterion
     */
    public function destroy(ReviewCriteria $criteria)
    {
        $criteria->delete();

        return back()->with('success', 'Criteria deleted successfully!');
    }
}
