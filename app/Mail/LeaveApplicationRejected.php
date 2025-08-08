<?php

namespace App\Mail;

use App\Models\LeaveApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LeaveApplicationRejected extends Mailable
{
use Queueable, SerializesModels;

public $leaveApplication;
public $subject = 'Your Leave Application Has Been Rejected'; // Define the subject

public function __construct(LeaveApplication $leaveApplication)
{
$this->leaveApplication = $leaveApplication;
}

public function build()
{
return $this->markdown('emails.leave.rejected');
}
}
