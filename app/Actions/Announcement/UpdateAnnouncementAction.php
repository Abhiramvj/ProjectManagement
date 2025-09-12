<?php

namespace App\Actions\Announcement;

use App\Models\Announcement;

class UpdateAnnouncementAction
{
    public function execute(Announcement $announcement, array $data): Announcement
    {
        $announcement->update([
            'title' => $data['title'],
            'content' => $data['content'],
        ]);

        return $announcement;
    }
}
