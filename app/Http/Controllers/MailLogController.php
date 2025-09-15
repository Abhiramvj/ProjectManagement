<?php

namespace App\Http\Controllers;

use App\Actions\MailLogs\GetMailLogsAction;
use App\Http\Resources\MailLogResource;
use App\Models\MailLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MailLogController extends Controller
{
    protected GetMailLogsAction $getMailLogsAction;

    public function __construct(GetMailLogsAction $getMailLogsAction)
    {
        $this->getMailLogsAction = $getMailLogsAction;
    }

    public function index(Request $request): Response
    {
        $mailLogs = $this->getMailLogsAction->execute($request);

        return Inertia::render('MailLogs/Index', [
            'mailLogs' => $mailLogs,
            'filters' => $request->only(['search']),
        ]);
    }

    public function show(MailLog $mailLog): Response
    {
        return Inertia::render('MailLogs/Show', [
            'mailLog' => new MailLogResource($mailLog),
        ]);
    }
}
