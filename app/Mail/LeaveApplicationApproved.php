<?php

namespace App\Mail;

use App\Models\LeaveApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
// ADD THIS IMPORT
use Illuminate\Queue\SerializesModels;

class LeaveApplicationApproved extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public LeaveApplication $leaveApplication) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Leave Application Approved');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.leave.approved');
    }

    /**
     * Get the message headers.
     *
     * THIS IS THE CRUCIAL PART
     */
    public function headers(): Headers
    {
        return new Headers(
            text: [
                'X-Leave-Application-ID' => $this->leaveApplication->id,
                'X-Event-Type' => 'leave_approved', // A custom name for this event
            ],
        );
    }
}