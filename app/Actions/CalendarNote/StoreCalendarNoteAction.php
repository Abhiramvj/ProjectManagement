<?php

namespace App\Actions\CalendarNote;

use App\Models\CalendarNote;
use Illuminate\Support\Facades\Auth;

class StoreCalendarNoteAction
{
    public function execute(array $data): CalendarNote
    {
        return CalendarNote::create([
            'user_id' => Auth::id(),
            'date'    => $data['date'],
            'note'    => $data['note'],
        ]);
    }
}
