<?php

namespace App\Actions\Announcement;

use App\Events\AnnouncementCreated;
use App\Models\Announcement;
use Illuminate\Support\Facades\Auth;

class StoreAnnouncementAction
{
    /**
     * Execute the creation of a new announcement.
     *
     * @param array $data
     * @return Announcement
     */
    public function execute(array $data): Announcement
    {
        $user = Auth::user();

        $announcement = $user->announcements()->create([
            'title'        => $data['title'],
            'content'      => $data['content'],
            'published_at' => now(),
        ]);

        broadcast(new AnnouncementCreated($announcement))->toOthers();

        return $announcement;
    }
}
