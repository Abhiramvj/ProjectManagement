<?php

namespace App\Mail;

use App\Models\LeaveApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LeaveApplicationSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public $leaveApplication;
    public $subject = 'New Leave Application Submitted'; // Define the subject

    public function __construct(LeaveApplication $leaveApplication)
    {
        $this->leaveApplication = $leaveApplication;
    }

  public function build()
{
    return $this->subject($this->subject)
                ->markdown('emails.leave.submitted');
}

}
