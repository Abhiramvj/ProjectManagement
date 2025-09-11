<?php

namespace App\Actions\CalendarNote;

use App\Models\CalendarNote;
use Illuminate\Support\Facades\Auth;

class UpdateCalendarNoteAction
{
    public function execute(CalendarNote $calendarNote, array $data): CalendarNote
    {
        // Ensure the user owns this note
        if ($calendarNote->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $calendarNote->update([
            'note' => $data['note'],
        ]);

        return $calendarNote;
    }
}
