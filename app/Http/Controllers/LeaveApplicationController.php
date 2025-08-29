<?php

namespace App\Http\Controllers;

use App\Actions\Leave\GetLeave;
use App\Actions\Leave\StoreLeave;
use App\Actions\Leave\UpdateLeave;
use App\Http\Requests\Leave\StoreLeaveRequest;
use App\Http\Requests\Leave\UpdateLeaveRequest;
use App\Mail\CustomMail;
use App\Mail\DynamicTemplateMail;
use App\Mail\LeaveApplicationApproved;
use App\Mail\LeaveApplicationRejected;
use App\Mail\LeaveApplicationSubmitted;
use App\Models\LeaveApplication;
use App\Models\MailTemplate;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Mail;
use App\Models\LeaveLog; // <-- IMPORT THE LEAVELOG MODEL
use App\Models\MailLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailable;
use Illuminate\Routing\Controller;
use Jenssegers\Mongodb\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;


class LeaveApplicationController extends Controller
{
    public function index(GetLeave $getLeaveRequests)
    {
        return Inertia::render('Leave/Index', $getLeaveRequests->handle());
    }


public function store(StoreLeaveRequest $request, StoreLeave $storeLeave)
{
    try {
        $leave_application = $storeLeave->handle($request->validated());

        LeaveLog::create([
            'user_id' => $leave_application->user_id,
            'actor_id' => auth()->id(),
            'leave_application_id' => $leave_application->id,
            'action' => 'created',
            'description' => 'Submitted a new leave request for ' . $leave_application->leave_days . ' day(s).',
            'details' => [
                'leave_type' => $leave_application->leave_type,
                'start_date' => $leave_application->start_date->toDateString(),
                'end_date' => $leave_application->end_date->toDateString(),
            ],
        ]);

        $recipients = User::role(['admin', 'hr'])->pluck('email')->toArray();
        $template   = MailTemplate::where('event_type', 'leave_submitted')->first(); // ✅ fixed field name

       if ($template && !empty($recipients)) {
    // 🔄 Replace placeholders with actual values
    $body = str_replace(
        [
            '{{employee_name}}',
            '{{leave_type}}',
            '{{leave_days}}',
            '{{start_date}}',
            '{{end_date}}',
            '{{reason}}',
             '[[app_name]]',
        ],
        [
            $leave_application->user->name,
            ucfirst($leave_application->leave_type),
            $leave_application->leave_days,
            $leave_application->start_date->format('M d, Y'),
            $leave_application->end_date->format('M d, Y'),
            $leave_application->reason,
             config('app.name'),
        ],
        $template->body
    );

    // ✅ Send email
 // ✅ Send email
Mail::to($recipients)->queue(new CustomMail($template->subject, $body));



    // ✅ Log each recipient
    foreach ($recipients as $email) {
        MailLog::create([
            'leave_application_id' => $leave_application->id,
            'recipient_email'      => $email,
            'subject'              => $template->subject,
            'status'               => 'sent',
            'event_type'           => $template->event_type,
            'sent_at'              => now(),
            'body_html'            => $body, // ✅ replaced version, not raw
            'reason'               => $leave_application->reason,
            'leave_period'         => $leave_application->start_date->toDateString()
                                      . ' - ' .
                                      $leave_application->end_date->toDateString(),
        ]);
    }

        }
        return Redirect::route('leave.index')
            ->with('success', 'Leave application submitted successfully.');

    } catch (ValidationException $e) {
        return Redirect::back()
            ->withErrors($e->errors())
            ->with('error', 'There were validation errors.');
    } catch (\Exception $e) {
        Log::error('Failed to store leave application: ' . $e->getMessage());
        return Redirect::back()
            ->with('error', 'An unexpected server error occurred. Please try again later.');
    }
}



    public function approveCompOff(Request $request, User $user)
    {
        $validated = $request->validate([
            'comp_off_days' => ['required', 'numeric', 'min:0.5'],
            'reason' => ['required', 'string', 'min:5', 'max:255'],
        ]);

        $daysToCredit = (float) $validated['comp_off_days'];
        $reason = $validated['reason'];
        $actor = auth()->user();
        $oldBalance = $user->comp_off_balance;

        $user->increment('comp_off_balance', $daysToCredit);

        // --- ADD THIS LOGGING BLOCK ---
        LeaveLog::create([
            'user_id' => $user->id,
            'actor_id' => $actor->id,
            'action' => 'comp_off_credited',
            'description' => $actor->name.' credited '.$daysToCredit.' comp-off day(s). Reason: '.$reason,
            'details' => [
                'balance_type' => 'compensatory',
                'change_amount' => $daysToCredit,
                'old_balance' => $oldBalance,
                'new_balance' => $user->fresh()->comp_off_balance,
                'reason' => $reason,
            ],
        ]);

        return redirect()->back()->with('success', "Compensatory off of {$daysToCredit} days credited to user.");
    }

public function update(UpdateLeaveRequest $request, LeaveApplication $leave_application, UpdateLeave $updateLeaveStatus)
{
    $validatedData = $request->validated();
    $status = $validatedData['status'];
    $actor = auth()->user();
    $user = $leave_application->user;

    if ($status === 'approved') {
        $balanceType = in_array($leave_application->leave_type, ['compensatory']) ? 'comp_off_balance' : 'leave_balance';
        $oldBalance = $user->$balanceType;

        // Deduct balance inside handler
        $updateLeaveStatus->handle($leave_application, 'approved');

        // Log approval action
        LeaveLog::create([
            'user_id' => $leave_application->user_id,
            'actor_id' => $actor->id,
            'leave_application_id' => $leave_application->id,
            'action' => 'approved',
            'description' => 'Request approved by '.$actor->name.'. '.$leave_application->leave_days.' day(s) deducted.',
            'details' => [
                'balance_type' => str_replace('_balance', '', $balanceType),
                'change_amount' => -$leave_application->leave_days,
                'old_balance' => $oldBalance,
                'new_balance' => $user->fresh()->$balanceType,
            ],
        ]);

        $this->notifyLeaveStatus($leave_application, 'approved');

        // Fetch approval email template from MongoDB
        $template = MailTemplate::where('event_type', 'leave_approved')->first();

        if ($template && !empty($user->email)) {
            $body = str_replace(
                [
    '{{employee_name}}',
    '{{leave_type}}',
    '{{leave_days}}',
    '{{start_date}}',
    '{{end_date}}',
    '{{route}}',
    '{{app_name}}',
                ],

                [
    $user->name,
    ucfirst($leave_application->leave_type),
    $leave_application->leave_days ?: $leave_application->start_date->diffInDays($leave_application->end_date) + 1, // fix duration here
    $leave_application->start_date->format('M d, Y'),
    $leave_application->end_date->format('M d, Y'),
    route('leave.index'),
    config('app.name'),
                ],

                $template->body
            );

            Mail::to($user->email)->queue(new CustomMail($template->subject, $body));

            // Log mail sent
            MailLog::create([
                'leave_application_id' => $leave_application->id,
                'recipient_email' => $user->email,
                'subject' => $template->subject,
                'status' => 'sent',
                'event_type' => $template->event_type,
                'sent_at' => now(),
                'body_html' => $body,
                'leave_period' => $leave_application->start_date->toDateString().' - '.$leave_application->end_date->toDateString(),
            ]);
        }
    } elseif ($status === 'rejected') {
        $rejectReason = $validatedData['rejection_reason'] ?? 'No reason provided.';
        $balanceType = null;
        $oldBalance = 0;

        if (in_array($leave_application->leave_type, ['annual', 'personal'])) {
            $user->increment('leave_balance', $leave_application->leave_days);
            $balanceType = 'leave_balance';
        } elseif ($leave_application->leave_type === 'compensatory') {
            $user->increment('comp_off_balance', $leave_application->leave_days);
            $balanceType = 'comp_off_balance';
        }

        if ($user && $balanceType) {
            $oldBalance = $user->$balanceType;
        }
        $user->save();

        // Restore balance inside handler
        $updateLeaveStatus->handle($leave_application, 'rejected', $rejectReason);

        // Log rejection action
        LeaveLog::create([
            'user_id' => $leave_application->user_id,
            'actor_id' => $actor->id,
            'leave_application_id' => $leave_application->id,
            'action' => 'rejected',
            'description' => 'Request rejected by '.$actor->name.'. Balance restored.',
            'details' => [
                'rejection_reason' => $rejectReason,
                'balance_type' => str_replace('_balance', '', $balanceType),
                'change_amount' => +$leave_application->leave_days,
                'old_balance' => $oldBalance,
                'new_balance' => $user->fresh()->$balanceType,
            ],
        ]);

        // Fetch rejection email template from MongoDB
        $template = MailTemplate::where('event_type', 'leave_rejected')->first();

        if ($template && !empty($user->email)) {
           $body = str_replace(
    [
        '{{employee_name}}',
        '{{leave_type}}',
        '{{leave_days}}',
        '{{start_date}}',
        '{{end_date}}',
        '{{rejection_reason}}',
        '{{route}}',
        '{{app_name}}',
    ],
    [
        $user->name,
        ucfirst($leave_application->leave_type),
        $leave_application->leave_days ?: $leave_application->start_date->diffInDays($leave_application->end_date) + 1,
        $leave_application->start_date->format('M d, Y'),
        $leave_application->end_date->format('M d, Y'),
        $rejectReason ?? 'No reason provided',
        route('leave.index'),
        config('app.name'),
    ],
    $template->body

            );

            Mail::to($user->email)->queue(new CustomMail($template->subject, $body));

            MailLog::create([
                'leave_application_id' => $leave_application->id,
                'recipient_email' => $user->email,
                'subject' => $template->subject,
                'status' => 'sent',
                'event_type' => $template->event_type,
                'sent_at' => now(),
                'body_html' => $body,
                'rejection_reason' => $rejectReason,
                'leave_period' => $leave_application->start_date->toDateString().' - '.$leave_application->end_date->toDateString(),
            ]);
        }
    }

    return Redirect::back()->with('success', 'Application status updated.');
}


    public function updateReason(Request $request, LeaveApplication $leave_application)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);
        $leave_application->update(['reason' => $validated['reason']]);

        return back()->with('success', 'Reason updated.');
    }

    public function cancel(LeaveApplication $leave_application)
    {
        if ($leave_application->user_id !== auth()->id() || $leave_application->status !== 'pending') {
            abort(403, 'Unauthorized action.');
        }

        // --- Important: We must restore the balance BEFORE deleting ---
        $user = $leave_application->user;
        if (in_array($leave_application->leave_type, ['annual', 'personal'])) {
            $user->increment('leave_balance', $leave_application->leave_days);
        } elseif ($leave_application->leave_type === 'compensatory') {
            $user->increment('comp_off_balance', $leave_application->leave_days);
        }

        // --- ADD THIS LOGGING BLOCK ---
        LeaveLog::create([
            'user_id' => $leave_application->user_id,
            'actor_id' => auth()->id(),
            'leave_application_id' => $leave_application->id,
            'action' => 'cancelled',
            'description' => 'User cancelled their pending leave request.',
        ]);

        $leave_application->delete();

        return Redirect::route('leave.index')->with('success', 'Leave request canceled.');
    }

    public function uploadDocument(Request $request, LeaveApplication $leave_application)
    {
        if ($leave_application->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'supporting_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $path = $request->file('supporting_document')->store('leave_documents/'.auth()->id(), 'public');

        if ($leave_application->supporting_document_path) {
            Storage::disk('public')->delete($leave_application->supporting_document_path);
        }

        $leave_application->update([
            'supporting_document_path' => $path,
        ]);

        return redirect()->back()->with('success', 'Supporting document uploaded successfully.');
    }

      private function notifyLeaveStatus(LeaveApplication $leaveApplication, string $status): void
    {
        try {
            $user = $leaveApplication->user; // the requester

            if ($status === 'approved') {
                $user->notify(new \App\Notifications\LeaveRequestApproved($leaveApplication));
            } elseif ($status === 'rejected') {
                $user->notify(new \App\Notifications\LeaveRequestRejected($leaveApplication));
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send leave status notification', [
                'error' => $e->getMessage(),
                'leave_id' => $leaveApplication->id,
                'status' => $status,
            ]);
        }
    }

private function sendEmail(LeaveApplication $leaveApplication, Mailable $mailable, string|array $recipientEmail): void
{
    $emailHtmlContent = Blade::render($mailable->templateBody, $mailable->data);

    $eventType = $mailable->eventType ?? 'unknown_event';
    $recipients = is_array($recipientEmail) ? $recipientEmail : [$recipientEmail];

    try {
        Mail::to($recipients)->queue($mailable);

        // LOG: Separate entry for each recipient
        foreach ($recipients as $email) {
            MailLog::create([
                'leave_application_id' => $leaveApplication->id,
                'recipient_email' => $email,  // <-- Log each one individually
                'subject' => $mailable->subject ?? 'Leave Application Notification',
                'event_type' => $eventType,
                'sent_at' => now(),
                'reason' => $leaveApplication->reason,
                'leave_period' => $leaveApplication->start_date->format('M d, Y').' to '.$leaveApplication->end_date->format('M d, Y'),
                'body_html' => $emailHtmlContent,
                'status' => 'sent',
                'error_message' => null,
            ]);
        }
    } catch (\Exception $e) {
        foreach ($recipients as $email) {
            MailLog::create([
                'leave_application_id' => $leaveApplication->id,
                'recipient_email' => $email,
                'subject' => $mailable->subject ?? 'Leave Application Notification',
                'event_type' => $eventType,
                'sent_at' => now(),
                'reason' => $leaveApplication->reason,
                'leave_period' => $leaveApplication->start_date->format('M d, Y').' to '.$leaveApplication->end_date->format('M d, Y'),
                'body_html' => $emailHtmlContent,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}


    private function getEventTypeFromMailable(Mailable $mailable): string
    {
        // This handles modern Laravel Mailables (Laravel 9+)
        if (method_exists($mailable, 'headers')) {
            // NEW, CORRECT WAY: get the header and check if the result is not null.
            $header = $mailable->headers()->get('X-Event-Type');

            if ($header) {
                // The getBodyAsString() method safely returns the header's value.
                return $header->getBodyAsString();
            }
        }

        // Fallback if the header isn't set or for older mailables.
        // It uses the Mailable's class name as the event type.
        $path = explode('\\', get_class($mailable));

        return array_pop($path);
    }
}
