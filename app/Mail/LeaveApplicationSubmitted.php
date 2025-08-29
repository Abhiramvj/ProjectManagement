<?php

namespace App\Mail;

use App\Models\LeaveApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LeaveApplicationSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    // --- 1. ADD THIS PUBLIC PROPERTY ---
    /**
     * A string identifier for the type of event this email represents.
     * This is much easier to access than a header.
     */
    public string $eventType = 'leave_submitted';

    /**
     * Create a new message instance.
     * We keep the public property on the constructor for easy access.
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
            subject: 'New Leave Application Submitted for Review',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.leave.submitted',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}