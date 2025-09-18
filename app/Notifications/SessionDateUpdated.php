<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class SessionDateUpdated extends Notification
{
    use Queueable;

    protected $session;

    public function __construct($session)
    {
        $this->session = $session;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

   public function toDatabase($notifiable)
{
    $formattedDate = $this->session->requested_date
        ? $this->session->requested_date->format('F j, Y')
        : 'not set';

    return [
        'session_id' => $this->session->id,
        'message' => "The date for your session '{$this->session->topic}' has been updated to " . ($this->session->date ? $this->session->date->format('F j, Y') : 'not set') . ".",

    ];
}

}
