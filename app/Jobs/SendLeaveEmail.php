<?php

namespace App\Jobs;

use App\Models\LeaveApplication;
use App\Models\MailLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendLeaveEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The leave application instance.
     *
     * @var \App\Models\LeaveApplication
     */
    protected $leaveApplication;

    /**
     * The mailable instance.
     *
     * @var \Illuminate\Mail\Mailable
     */
    protected $mailable;

    /**
     * The recipient's email address.
     *
     * @var string
     */
    protected $recipientEmail;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(LeaveApplication $leaveApplication, Mailable $mailable, string $recipientEmail)
    {
        $this->leaveApplication = $leaveApplication;
        $this->mailable = $mailable;
        $this->recipientEmail = $recipientEmail;
    }

    /**
     * Execute the job.
     *
     * This method contains the exact logic from your original sendEmail function.
     */
    public function handle(): void
    {
        $emailHtmlContent = $this->mailable->render();
        $eventType = $this->mailable->eventType ?? 'unknown_event';

        $logData = [
            'leave_application_id' => $this->leaveApplication->id,
            'recipient_email' => $this->recipientEmail,
            'subject' => $this->mailable->subject ?? 'Leave Application Notification',
            'event_type' => $eventType,
            'sent_at' => now(),
            'reason' => $this->leaveApplication->reason,
            'leave_period' => $this->leaveApplication->start_date->format('M d, Y').' to '.$this->leaveApplication->end_date->format('M d, Y'),
            'body_html' => $emailHtmlContent,
        ];

        try {
            // Send the actual email.
            Mail::to($this->recipientEmail)->queue($this->mailable);

            // LOG SUCCESS
            MailLog::create(array_merge($logData, [
                'status' => 'sent',
                'error_message' => null,
            ]));

        } catch (\Exception $e) {
            // LOG FAILURE
            Log::error(
                'Mail sending job failed for LeaveApplication ID '.$this->leaveApplication->id.
                ' to '.$this->recipientEmail.': '.$e->getMessage()
            );

            MailLog::create(array_merge($logData, [
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]));

            // Optional: re-throw the exception to make the job fail and retry
            // $this->fail($e);
        }
    }
}
