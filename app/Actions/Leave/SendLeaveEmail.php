<?php

namespace App\Actions\Leave;

use App\Models\LeaveApplication;
use App\Models\MailLog;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class SendLeaveEmail
{
    public function handle(LeaveApplication $leaveApplication, Mailable $mailable, string|array $recipientEmail): void
    {
        $recipients = is_array($recipientEmail) ? $recipientEmail : [$recipientEmail];
        $eventType = $mailable->eventType ?? 'unknown_event';

        try {
            Mail::to($recipients)->queue($mailable);

            foreach ($recipients as $email) {
                MailLog::create([
                    'leave_application_id' => $leaveApplication->id,
                    'recipient_email' => $email,
                    'subject' => $mailable->subject ?? 'Leave Application Notification',
                    'event_type' => $eventType,
                    'sent_at' => now(),
                    'reason' => $leaveApplication->reason,
                    'leave_period' => $leaveApplication->start_date->format('M d, Y').' to '.$leaveApplication->end_date->format('M d, Y'),
                    'body_html' => method_exists($mailable, 'render') ? $mailable->render() : null,
                    'status' => 'sent',
                    'error_message' => null,
                ]);
            }
        } catch (\Exception $e) {
            foreach ($recipients as $email) {
                MailLog::create([
                    'leave_application_id' => $leaveApplication->id,
                    'recipient_email' => $email,
                    'subject' => $mailable->subject ?? 'Leave Application Notification',
                    'event_type' => $eventType,
                    'sent_at' => now(),
                    'reason' => $leaveApplication->reason,
                    'leave_period' => $leaveApplication->start_date->format('M d, Y').' to '.$leaveApplication->end_date->format('M d, Y'),
                    'body_html' => method_exists($mailable, 'render') ? $mailable->render() : null,
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }
        }
    }
}
