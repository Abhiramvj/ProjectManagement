<?php

// app/Http/Controllers/AnnouncementController.php
namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        Auth::user()->announcements()->create([
            'title' => $request->title,
            'content' => $request->content,
            'published_at' => now(), // Publish immediately
        ]);

        return redirect()->back()->with('success', 'Announcement created successfully.');
    }

    public function update(Request $request, Announcement $announcement)
    {
        // Add authorization if needed, e.g., Gate::authorize('update', $announcement);
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $announcement->update($request->only('title', 'content'));

        return redirect()->back()->with('success', 'Announcement updated successfully.');
    }

    public function destroy(Announcement $announcement)
    {
        // Add authorization if needed, e.g., Gate::authorize('delete', $announcement);
        $announcement->delete();

        return redirect()->back()->with('success', 'Announcement deleted successfully.');
    }
}