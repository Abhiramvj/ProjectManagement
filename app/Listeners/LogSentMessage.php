<?php

namespace App\Listeners;

use App\Models\MailLog; // <-- Import your MongoDB model
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Arr;

class LogSentMessage
{
    /**
     * Handle the event.
     *
     * @param  \Illuminate\Mail\Events\MessageSent  $event
     * @return void
     */
    public function handle(MessageSent $event): void
    {
        // Get the custom headers we attached in the Mailable
        $headers = $event->message->getHeaders();
        $leaveApplicationId = $headers->getHeaderBody('X-Leave-Application-ID');
        $eventType = $headers->getHeaderBody('X-Event-Type');

        // Get the recipient's email address safely
        $recipient = Arr::first($event->message->getTo());

        // Create the log entry in your MongoDB 'mail_logs' collection
        MailLog::create([
            'leave_application_id' => $leaveApplicationId,
            'recipient_email' => $recipient ? $recipient->getAddress() : 'unknown',
            'subject' => $event->message->getSubject(),
            'status' => 'sent', // This event only fires on successful sends
            'event_type' => $eventType ?? 'general_mail',
            'sent_at' => now(),
            'error_message' => null, 
        ]);
    }
}
