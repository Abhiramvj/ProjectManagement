<?php

namespace App\Services;

use App\Mail\CustomMail;
use App\Models\LeaveApplication;
use App\Models\MailLog;
use App\Models\MailTemplate;
use App\Models\TemplateMapping;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Mail;

class LeaveNotificationService
{
    public static function sendLeaveSubmittedNotification($leaveApplication)
    {

        $recipients = User::role(['admin', 'hr'])->pluck('email')->toArray();

        $teamLeads = $leaveApplication->user->teamLeads();

        foreach ($teamLeads as $lead) {
            if ($lead->email) {
                $recipients[] = $lead->email;
            }
        }

        $recipients = array_unique($recipients);

        $mapping = TemplateMapping::where('event_type', 'leave_submitted')->first();

        $template = $mapping
            ? MailTemplate::find($mapping->mail_template_id)
            : MailTemplate::where('event_type', 'leave_submitted')->first();

        if ($template && ! empty($recipients)) {
            $body = str_replace(
                [
                    '{{employee_name}}',
                    '{{leave_type}}',
                    '{{leave_days}}',
                    '{{start_date}}',
                    '{{end_date}}',
                    '{{reason}}',
                    '[[app_name]]',
                ],
                [
                    $leaveApplication->user->name,
                    ucfirst($leaveApplication->leave_type),
                    $leaveApplication->leave_days,
                    $leaveApplication->start_date->format('M d, Y'),
                    $leaveApplication->end_date->format('M d, Y'),
                    $leaveApplication->reason,
                    config('app.name'),
                ],
                $template->body
            );

            Mail::to($recipients)->queue(new CustomMail($template->subject, $body, $leaveApplication->supporting_document_path));

            foreach ($recipients as $email) {
                MailLog::create([
                    'leave_application_id' => $leaveApplication->id,
                    'recipient_email' => $email,
                    'subject' => $template->subject,
                    'status' => 'sent',
                    'event_type' => $template->event_type,
                    'sent_at' => now(),
                    'body_html' => $body,
                    'reason' => $leaveApplication->reason,
                    'leave_period' => $leaveApplication->start_date->toDateString().' - '.$leaveApplication->end_date->toDateString(),
                ]);
            }
        }
    }

    public static function sendLeaveApprovedNotification($leaveApplication)
    {
        self::notifyLeaveStatus($leaveApplication, 'approved');
        $user = $leaveApplication->user;
        $mapping = TemplateMapping::where('event_type', 'leave_approved')->first();

        $template = $mapping
            ? MailTemplate::find($mapping->mail_template_id)
            : MailTemplate::where('event_type', 'leave_approved')->first();

        if ($template && ! empty($user->email)) {
            $body = str_replace(
                [
                    '{{employee_name}}',
                    '{{leave_type}}',
                    '{{leave_days}}',
                    '{{start_date}}',
                    '{{end_date}}',
                    '{{route}}',
                    '{{app_name}}',
                ],
                [
                    $user->name,
                    ucfirst($leaveApplication->leave_type),
                    $leaveApplication->leave_days ?: $leaveApplication->start_date->diffInDays($leaveApplication->end_date) + 1,
                    $leaveApplication->start_date->format('M d, Y'),
                    $leaveApplication->end_date->format('M d, Y'),
                    route('leave.index'),
                    config('app.name'),
                ],
                $template->body
            );

            Mail::to($user->email)->queue(new CustomMail($template->subject, $body));

            MailLog::create([
                'leave_application_id' => $leaveApplication->id,
                'recipient_email' => $user->email,
                'subject' => $template->subject,
                'status' => 'sent',
                'event_type' => $template->event_type,
                'sent_at' => now(),
                'body_html' => $body,
                'leave_period' => $leaveApplication->start_date->toDateString().' - '.$leaveApplication->end_date->toDateString(),
            ]);
        }
    }

    public static function sendLeaveRejectedNotification($leaveApplication)
    {
        self::notifyLeaveStatus($leaveApplication, 'rejected');
        $user = $leaveApplication->user;

        // Get mapped template if exists
        $mapping = TemplateMapping::where('event_type', 'leave_rejected')->first();

        $template = $mapping
            ? MailTemplate::find($mapping->mail_template_id)
            : MailTemplate::where('event_type', 'leave_rejected')->first();

        if ($template && ! empty($user->email)) {
            $body = str_replace(
                [
                    '{{employee_name}}',
                    '{{leave_type}}',
                    '{{leave_days}}',
                    '{{start_date}}',
                    '{{end_date}}',
                    '{{reason}}',
                    '{{rejection_reason}}',
                    '{{app_name}}',
                ],
                [
                    $user->name,
                    ucfirst($leaveApplication->leave_type),
                    $leaveApplication->leave_days ?: $leaveApplication->start_date->diffInDays($leaveApplication->end_date) + 1,
                    $leaveApplication->start_date->format('M d, Y'),
                    $leaveApplication->end_date->format('M d, Y'),
                    $leaveApplication->reason,
                    $leaveApplication->rejection_reason ?? 'No specific reason provided',
                    config('app.name'),
                ],
                $template->body
            );

            Mail::to($user->email)->queue(new CustomMail($template->subject, $body));

            MailLog::create([
                'leave_application_id' => $leaveApplication->id,
                'recipient_email' => $user->email,
                'subject' => $template->subject,
                'status' => 'sent',
                'event_type' => $template->event_type,
                'sent_at' => now(),
                'body_html' => $body,
                'reason' => $leaveApplication->reason,
                'leave_period' => $leaveApplication->start_date->toDateString().' - '.$leaveApplication->end_date->toDateString(),
            ]);
        }
    }

    public static function notifyLeaveStatus(LeaveApplication $leaveApplication, string $status): void
    {
        try {
            $user = $leaveApplication->user;

            if ($status === 'approved') {
                $user->notify(new \App\Notifications\LeaveRequestApproved($leaveApplication));
            } elseif ($status === 'rejected') {
                $user->notify(new \App\Notifications\LeaveRequestRejected($leaveApplication));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send leave status notification', [
                'error' => $e->getMessage(),
                'leave_id' => $leaveApplication->id,
                'status' => $status,
            ]);
        }
    }

    public function sendEmail(LeaveApplication $leaveApplication, Mailable $mailable, string|array $recipientEmail): void
    {
        $emailHtmlContent = Blade::render($mailable->templateBody, $mailable->data);
        $eventType = $mailable->eventType ?? 'unknown_event';
        $recipients = is_array($recipientEmail) ? $recipientEmail : [$recipientEmail];

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
                    'body_html' => $emailHtmlContent,
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
                    'body_html' => $emailHtmlContent,
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }
        }
    }

    public function getEventTypeFromMailable(Mailable $mailable): string
    {
        if (method_exists($mailable, 'headers')) {
            $header = $mailable->headers()->get('X-Event-Type');
            if ($header) {
                return $header->getBodyAsString();
            }
        }
        $path = explode('\\', get_class($mailable));

        return array_pop($path);
    }
}
