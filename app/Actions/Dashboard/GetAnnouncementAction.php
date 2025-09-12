<?php

namespace App\Actions\Dashboard;

use App\Models\Announcement;

class GetAnnouncementAction
{
    public function execute(): array
    {
        return Announcement::with('user:id,name,avatar_url')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($announcement) {
                return [
                    'id' => $announcement->id,
                    'title' => $announcement->title,
                    'content' => $announcement->content,
                    'author' => $announcement->user,
                    'created_at_formatted' => $announcement->created_at->format('M d, Y'),
                ];
            })->toArray();
    }
}
