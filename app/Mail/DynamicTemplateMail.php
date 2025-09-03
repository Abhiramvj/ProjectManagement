<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Blade;

class DynamicTemplateMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $subjectText;
    public string $templateBody;
    public array $data;
    public string $eventType; // <-- add event type

    /**
     * @param string $subject       The subject line of the email
     * @param string $templateBody  The full Blade/Markdown body (from MongoDB)
     * @param array $data           Data to inject into the template
     * @param string $eventType     Type of mail event (e.g. leave_submitted, leave_approved, leave_rejected)
     */
    public function __construct(string $subject, string $templateBody, array $data = [], string $eventType = 'unknown_event')
    {
        $this->subjectText  = $subject;
        $this->templateBody = $templateBody;
        $this->data         = $data;
        $this->eventType    = $eventType;
    }

    public function build()
    {
        $renderedBody = Blade::render($this->templateBody, $this->data);

        return $this->subject($this->subjectText)
                    ->html($renderedBody);
    }
}
