<?php

namespace App\Services;

use App\Models\TemplateMapping;
use App\Models\MailTemplate;
use App\Models\User;
use App\Models\MailLog;
use App\Mail\CustomMail;
use Illuminate\Support\Facades\Mail;

class LeaveNotificationService
{
    public static function sendLeaveSubmittedNotification($leaveApplication)
    {
        $recipients = User::role(['admin', 'hr'])->pluck('email')->toArray();

        $mapping = TemplateMapping::where('event_type', 'leave_submitted')->first();

        $template = $mapping
            ? MailTemplate::find($mapping->mail_template_id)
            : MailTemplate::where('event_type', 'leave_submitted')->first();

        if ($template && !empty($recipients)) {
            $body = str_replace(
                [
                    '{{employee_name}}',
                    '{{leave_type}}',
                    '{{leave_days}}',
                    '{{start_date}}',
                    '{{end_date}}',
                    '{{reason}}',
                    '[[app_name]]'
                ],
                [
                    $leaveApplication->user->name,
                    ucfirst($leaveApplication->leave_type),
                    $leaveApplication->leave_days,
                    $leaveApplication->start_date->format('M d, Y'),
                    $leaveApplication->end_date->format('M d, Y'),
                    $leaveApplication->reason,
                    config('app.name')
                ],
                $template->body
            );

            Mail::to($recipients)->queue(new CustomMail($template->subject, $body));

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
                    'leave_period' => $leaveApplication->start_date->toDateString() . ' - ' . $leaveApplication->end_date->toDateString(),
                ]);
            }
        }
    }

     public static function sendLeaveApprovedNotification($leaveApplication)
    {
        $user = $leaveApplication->user;
        $mapping = TemplateMapping::where('event_type', 'leave_approved')->first();

        $template = $mapping
            ? MailTemplate::find($mapping->mail_template_id)
            : MailTemplate::where('event_type', 'leave_approved')->first();

        if ($template && !empty($user->email)) {
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
                'leave_period' => $leaveApplication->start_date->toDateString() . ' - ' . $leaveApplication->end_date->toDateString(),
            ]);
        }
    }
}
