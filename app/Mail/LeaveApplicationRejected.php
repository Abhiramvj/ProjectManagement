<?php

namespace App\Mail;

use App\Models\LeaveApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// Import the Headers class
use Illuminate\Mail\Mailables\Headers;

class LeaveApplicationRejected extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * The "public" keyword makes the variable automatically available to your Blade view.
     */
    public function __construct(
        public LeaveApplication $leaveApplication
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Leave Application Rejected',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // This points to the Blade template for the rejection email.
        return new Content(
            markdown: 'emails.leave.rejected',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Get the message headers.
     * This adds custom data for our event listener to log.
     */
    public function headers(): Headers
    {
        return new Headers(
            text: [
                'X-Leave-Application-ID' => $this->leaveApplication->id,
                'X-Event-Type' => 'leave_rejected',
            ],
        );
    }
}
