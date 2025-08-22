<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MailLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // 'this' refers to the MailLog model instance passed to the resource.
        // We are transforming the raw model data into a clean, frontend-ready structure.

        return [
            // Top-level information
            'id' => $this->id, // or $this->_id
            'recipient_email' => $this->recipient_email,
            'subject' => $this->subject,

            // =======================================================================
            //                             THE FIX
            // This line adds the raw HTML body to the data sent to the frontend.
            // =======================================================================
            'body_html'           => $this->body_html,

            // A dedicated object for the details shown in the gray box
            'details' => [
                'leave_period' => $this->leave_period, // Already formatted in your sendEmail function
                'reason' => $this->reason,
            ],

            // A dedicated object for the log metadata
            'log_info' => [
                'application_id' => $this->leave_application_id,
                'event_type' => ucwords(str_replace('_', ' ', $this->event_type)), // e.g., "leave_submitted" -> "Leave Submitted"
                'status' => ucfirst($this->status), // e.g., "failed" -> "Failed"
                'sent_at' => $this->sent_at ? $this->sent_at->format('M d, Y \a\t h:i:s A') : 'Not available', // e.g., "Aug 13, 2025 at 06:01:07 PM"
                'status_class' => $this->getStatusClasses(),
            ],
        ];
    }

    /**
     * A helper function to determine the CSS classes for the status badge.
     * Logic is kept on the backend, away from the template.
     */
    protected function getStatusClasses(): string
    {
        return match ($this->status) {
            'sent' => 'bg-green-100 text-green-800',
            'failed' => 'bg-red-100 text-red-800',
            default => 'bg-yellow-100 text-yellow-800',
        };
    }
}
