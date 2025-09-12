<?php

// app/Http/Controllers/AnnouncementController.php

namespace App\Http\Controllers;

use App\Actions\Announcement\StoreAnnouncementAction;
use App\Actions\Announcement\UpdateAnnouncementAction;
use App\Http\Requests\Announcement\AnnouncementStoreRequest;
use App\Http\Requests\Announcement\UpdateAnnouncementRequest;
use App\Models\Announcement;

class AnnouncementController extends Controller
{
    public function store(AnnouncementStoreRequest $request, StoreAnnouncementAction $action)
    {
        $action->execute($request->validated());

        return redirect()->back()->with('success', 'Announcement created and published successfully.');
    }

    public function update(UpdateAnnouncementRequest $request, Announcement $announcement, UpdateAnnouncementAction $action)
    {
        $action->execute($announcement, $request->validated());

        return redirect()->back()->with('success', 'Announcement updated successfully.');
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();

        return redirect()->back()->with('success', 'Announcement deleted successfully.');
    }
}
