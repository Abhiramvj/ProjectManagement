<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class SessionCompleted extends Notification
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
        return [
            'session_id' => $this->session->id,
            'message' => "Your session '{$this->session->topic}' has been completed.",
        ];
    }
}
