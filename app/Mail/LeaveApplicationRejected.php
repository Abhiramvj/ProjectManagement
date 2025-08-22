<?php

namespace App\Mail;

use App\Models\LeaveApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LeaveApplicationRejected extends Mailable
{
    use Queueable, SerializesModels;

    public string $eventType = 'leave_rejected'; // For logging purposes

    // --- ADD A PUBLIC PROPERTY FOR THE REASON ---
    /**
     * The specific reason provided for the rejection.
     */
    public ?string $rejection_reason;

    /**
     * Create a new message instance.
     * We update the constructor to accept the rejection reason.
     */
    public function __construct(
        public LeaveApplication $leaveApplication,
        ?string $rejection_reason = null // Accept the reason here
    ) {
        // --- STORE THE REASON IN THE PUBLIC PROPERTY ---
        $this->rejection_reason = $rejection_reason;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Update on Your Leave Application',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // This points to your markdown file, which is correct.
        return new Content(
            markdown: 'emails.leave.rejected',
        );
    }
}
