<?php

namespace App\Http\Controllers;

use App\Models\MailLog; // <-- Import your MongoDB model
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Inertia\Inertia;     // <-- Import Inertia
use Inertia\Response;

class MailLogController extends Controller
{
    /**
     * Display a listing of the mail logs.
     */
    public function index(): Response
    {

        $mailLogs = MailLog::latest('sent_at')->paginate(15);


        return Inertia::render('MailLogs/Index', [
            'mailLogs' => $mailLogs,
        ]);
    }

    public function show(MailLog $mailLog): Response
    {
        // Because the route group is protected by the 'view mail logs' Gate,
        // this method will only be reachable if the Gate returns true.
        // No further authorization check is needed here.

        return Inertia::render('MailLogs/Show', [
            'mailLog' => $mailLog,
        ]);
    }

    //  public function showSnapshot(MailLog $mailLog): View
    // {
    //     // Laravel automatically finds the $mailLog from the ID in the URL via Route Model Binding.

    //     // The view() helper renders a Blade template from 'resources/views/',
    //     // not an Inertia/Vue component.
    //     return view('mail_logs.snapshot', [
    //         'mailLog' => $mailLog
    //     ]);
    // }
}
