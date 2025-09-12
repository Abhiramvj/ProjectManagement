<?php

namespace App\Http\Controllers;

use App\Actions\CalendarNote\DeleteCalendarNoteAction;
use App\Actions\CalendarNote\StoreCalendarNoteAction;
use App\Actions\CalendarNote\UpdateCalendarNoteAction;
use App\Http\Requests\CalendarNote\StoreCalendarNoteRequest;
use App\Http\Requests\CalendarNote\UpdateCalendarNoteRequest;
use App\Models\CalendarNote;

class CalendarNoteController extends Controller
{
    public function store(StoreCalendarNoteRequest $request, StoreCalendarNoteAction $action)
    {
        $action->execute($request->validated());

        return redirect()->back()->with('success', 'Note added successfully.');
    }

    /**
     * Update the specified calendar note in storage.
     */
    public function update(UpdateCalendarNoteRequest $request, CalendarNote $calendarNote, UpdateCalendarNoteAction $action)
    {
        $action->execute($calendarNote, $request->validated());

        return redirect()->back()->with('success', 'Note updated successfully.');
    }

    /**
     * Remove the specified calendar note from storage.
     */
    public function destroy(CalendarNote $calendarNote, DeleteCalendarNoteAction $action)
    {
        $action->execute($calendarNote);

        return redirect()->back()->with('success', 'Note deleted successfully.');
    }
}
