<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subjectLine;

    public $bodyContent;

    public $attachmentPath;

    public function __construct($subjectLine, $bodyContent, $attachmentPath = null)
    {
        $this->subjectLine = $subjectLine;
        $this->bodyContent = $bodyContent;
        $this->attachmentPath = $attachmentPath;
    }

    public function build()
    {
        $mail = $this->subject($this->subjectLine)
            ->html($this->bodyContent);
        if ($this->attachmentPath) {
            $mail->attach(storage_path('app/public/'.$this->attachmentPath));
        }

        return $mail;

    }
}
