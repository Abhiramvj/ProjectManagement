<?php

namespace App\Http\Controllers;

use App\Models\MailLog; // <-- Import your MongoDB model
use Illuminate\Http\Request;
use Inertia\Inertia;     // <-- Import Inertia

class MailLogController extends Controller
{
    /**
     * Display a listing of the mail logs.
     */
    public function index()
    {

        $mailLogs = MailLog::latest('sent_at')->paginate(15);

        // Render the Inertia Vue component and pass the paginated logs as a prop.
        return Inertia::render('MailLogs/Index', [
            'mailLogs' => $mailLogs,
        ]);
    }
}
