<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SessionApproved extends Notification
{
    use Queueable;

    protected $session;

    public function __construct($session)
    {
        $this->session = $session;
    }

    public function via($notifiable)
    {
        return ['database']; // add other channels if needed
    }

    public function toDatabase($notifiable)
    {
        $date = null;
        if ($this->session->date) {
            // Ensure date is a Carbon instance for formatting
            $date = is_string($this->session->date)
                ? \Carbon\Carbon::parse($this->session->date)
                : $this->session->date;
        }

        return [
            'session_id' => $this->session->id,
            'message' => "Your session '{$this->session->topic}' is approved and scheduled for " .
                ($date ? $date->format('F j, Y') : 'a date not yet set') . ".",
        ];
    }
}
