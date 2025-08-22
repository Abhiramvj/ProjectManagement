<?php

namespace App\Http\Controllers;

use App\Http\Resources\MailLogResource;
use App\Models\MailLog;     // Your MongoDB model
use Illuminate\Http\Request; // We need the Request object for filtering
use Inertia\Inertia;         // The Inertia facade
use Inertia\Response;        // The Inertia response type

class MailLogController extends Controller
{
    /**
     * Display a listing of the mail logs with search and filtering.
     */
    public function index(Request $request): Response
    {
        // 1. Start with a query builder instance
        $query = MailLog::query();

        // 2. Apply filters if they are present in the request URL (e.g., /mail-logs?search=sandra)
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('recipient_email', 'like', '%'.$searchTerm.'%')
                    ->orWhere('subject', 'like', '%'.$searchTerm.'%');
            });
        }

        // 3. Eager load and paginate the results, selecting only necessary columns for performance
        $mailLogs = $query->select([
            'id', // or '_id' depending on your model configuration
            'recipient_email',
            'subject',
            'event_type',
            'status',
            'sent_at',
        ])
            ->latest('sent_at') // Order by the most recent
            ->paginate(15)
        // This 'withQueryString' is crucial! It makes the pagination links remember the search query.
            ->withQueryString();

        return Inertia::render('MailLogs/Index', [
            // Pass the paginated logs to the Vue component
            'mailLogs' => $mailLogs,
            // Pass the current search filter back to the view so we can display it in the search box
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Display a specific mail log.
     * Route-model binding will automatically find the MailLog by its ID.
     */
    public function show(MailLog $mailLog): Response
    {
        // 2. Instead of passing the raw model, we wrap it in our resource.
        // The resource will transform the data into the perfect format.
        return Inertia::render('MailLogs/Show', [
            'mailLog' => new MailLogResource($mailLog),
        ]);
    }
}
