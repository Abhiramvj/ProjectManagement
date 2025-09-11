<?php

namespace App\Actions\CalendarNote;

use App\Models\CalendarNote;
use Illuminate\Support\Facades\Auth;

class DeleteCalendarNoteAction
{
    public function execute(CalendarNote $calendarNote): void
    {
        // Ensure the user owns this note
        if ($calendarNote->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $calendarNote->delete();
    }
}
