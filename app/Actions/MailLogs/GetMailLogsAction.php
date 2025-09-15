<?php

namespace App\Actions\MailLogs;

use App\Models\MailLog;
use Illuminate\Http\Request;

class GetMailLogsAction
{
    public function execute(Request $request)
    {
        $query = MailLog::query();

        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('recipient_email', 'like', '%'.$searchTerm.'%')
                    ->orWhere('subject', 'like', '%'.$searchTerm.'%');
            });
        }

        return $query->select([
            'id',
            'recipient_email',
            'subject',
            'event_type',
            'status',
            'sent_at',
        ])
            ->latest('sent_at')
            ->paginate(15)
            ->withQueryString();
    }
}
