<?php

namespace App\Http\Controllers;

use App\Models\ReviewCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReviewCategoryController extends Controller
{
    // Show the index page with all categories + criteria
    public function index()
    {
        $categories = ReviewCategory::with('criteria')->get();

        return Inertia::render('Reviews/CategoryIndex', [
            'categories' => $categories,
        ]);
    }

    // Store new category
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $category = ReviewCategory::create($validated);

        return redirect()->route('review-categories.index')
                         ->with('success', 'Category created successfully!');
    }
}
