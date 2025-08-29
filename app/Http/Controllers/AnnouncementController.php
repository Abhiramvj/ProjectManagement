<?php

// app/Http/Controllers/AnnouncementController.php

namespace App\Http\Controllers;

use App\Events\AnnouncementCreated;
use App\Models\Announcement; // 1. IMPORT THE EVENT CLASS
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    /**
     * Store a newly created announcement and broadcast it.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        // 2. CREATE the announcement and capture the new model in a variable.
        $announcement = Auth::user()->announcements()->create([
            'title' => $request->title,
            'content' => $request->content,
            'published_at' => now(), // Publish immediately
        ]);

        // 3. BROADCAST the event to all other connected users.
        // The .toOthers() helper prevents the user who created the announcement
        // from receiving the real-time notification themselves.
        broadcast(new AnnouncementCreated($announcement))->toOthers();

        return redirect()->back()->with('success', 'Announcement created and published successfully.');
    }

    /**
     * Update an existing announcement.
     */
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

    /**
     * Delete an announcement.
     */
    public function destroy(Announcement $announcement)
    {
        // Add authorization if needed, e.g., Gate::authorize('delete', $announcement);
        $announcement->delete();

        return redirect()->back()->with('success', 'Announcement deleted successfully.');
    }
}

