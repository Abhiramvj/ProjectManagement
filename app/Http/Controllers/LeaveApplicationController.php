<?php

namespace App\Http\Controllers;

use App\Actions\Leave\GetLeave;
use App\Actions\Leave\StoreLeave;
use App\Actions\Leave\UpdateLeave;
use App\Http\Requests\Leave\StoreLeaveRequest;
use App\Http\Requests\Leave\UpdateLeaveRequest;
use App\Mail\CustomMail;
use App\Mail\LeaveApplicationApproved;
use App\Mail\LeaveApplicationRejected;
use App\Mail\LeaveApplicationSubmitted;
use App\Models\TemplateMapping;
use App\Models\LeaveApplication;
use App\Models\LeaveLog;
use App\Models\MailLog;
use App\Models\MailTemplate;
use App\Services\LeaveNotificationService;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailable;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class LeaveApplicationController extends Controller
{
    use AuthorizesRequests;

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

            LeaveNotificationService::sendLeaveSubmittedNotification($leave_application);

            // Send notification emails to admin & HR
            $recipients = User::role(['admin', 'hr'])->get();
            if ($recipients->isNotEmpty()) {
                $emails = $recipients->pluck('email')->toArray();

                $this->sendEmail(
                    $leave_application,
                    new LeaveApplicationSubmitted($leave_application),
                    $emails
                );

                $template = MailTemplate::where('event_type', 'leave_submitted')->first();
                if ($template && !empty($emails)) {
                    $body = str_replace(
                        ['{{employee_name}}', '{{leave_type}}', '{{leave_days}}', '{{start_date}}', '{{end_date}}', '{{reason}}', '[[app_name]]'],
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

                    Mail::to($emails)->queue(new CustomMail($template->subject, $body));

                    foreach ($emails as $email) {
                        MailLog::create([
                            'leave_application_id' => $leave_application->id,
                            'recipient_email' => $email,
                            'subject' => $template->subject,
                            'status' => 'sent',
                            'event_type' => $template->event_type,
                            'sent_at' => now(),
                            'body_html' => $body,
                            'reason' => $leave_application->reason,
                            'leave_period' => $leave_application->start_date->toDateString() . ' - ' . $leave_application->end_date->toDateString(),
                        ]);
                    }
                }
            }

            return Redirect::route('leave.index')->with('success', 'Leave application submitted successfully.');
        } catch (ValidationException $e) {
            return Redirect::back()->withErrors($e->errors())->with('error', 'There were validation errors.');
        } catch (\Exception $e) {
            Log::error('Failed to store leave application: ' . $e->getMessage());
            return Redirect::back()->with('error', 'An unexpected server error occurred. Please try again later.');
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

        LeaveLog::create([
            'user_id' => $user->id,
            'actor_id' => $actor->id,
            'action' => 'comp_off_credited',
            'description' => $actor->name . ' credited ' . $daysToCredit . ' comp-off day(s). Reason: ' . $reason,
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

            $updateLeaveStatus->handle($leave_application, 'approved');

            LeaveLog::create([
                'user_id' => $leave_application->user_id,
                'actor_id' => $actor->id,
                'leave_application_id' => $leave_application->id,
                'action' => 'approved',
                'description' => 'Request approved by ' . $actor->name . '. ' . $leave_application->leave_days . ' day(s) deducted.',
                'details' => [
                    'balance_type' => str_replace('_balance', '', $balanceType),
                    'change_amount' => -$leave_application->leave_days,
                    'old_balance' => $oldBalance,
                    'new_balance' => $user->fresh()->$balanceType,
                ],
            ]);

            $this->notifyLeaveStatus($leave_application, 'approved');
            LeaveNotificationService::sendLeaveApprovedNotification($leave_application);

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
            $updateLeaveStatus->handle($leave_application, 'rejected', $rejectReason);

            LeaveLog::create([
                'user_id' => $leave_application->user_id,
                'actor_id' => $actor->id,
                'leave_application_id' => $leave_application->id,
                'action' => 'rejected',
                'description' => 'Request rejected by ' . $actor->name . '. Balance restored.',
                'details' => [
                    'rejection_reason' => $rejectReason,
                    'balance_type' => str_replace('_balance', '', $balanceType),
                    'change_amount' => +$leave_application->leave_days,
                    'old_balance' => $oldBalance,
                    'new_balance' => $user->fresh()->$balanceType,
                ],
            ]);

            $templateMapping = TemplateMapping::where('event_type', 'leave_rejected')->first();
            $template = $templateMapping
                ? MailTemplate::find($templateMapping->mail_template_id)
                : MailTemplate::where('event_type', 'leave_rejected')->first();

            if ($template && !empty($user->email)) {
                $body = str_replace(
                    ['{{employee_name}}', '{{leave_type}}', '{{leave_days}}', '{{start_date}}', '{{end_date}}', '{{rejection_reason}}', '{{route}}', '{{app_name}}'],
                    [
                        $user->name,
                        ucfirst($leave_application->leave_type),
                        $leave_application->leave_days ?: $leave_application->start_date->diffInDays($leave_application->end_date) + 1,
                        $leave_application->start_date->format('M d, Y'),
                        $leave_application->end_date->format('M d, Y'),
                        $rejectReason,
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
                    'leave_period' => $leave_application->start_date->toDateString() . ' - ' . $leave_application->end_date->toDateString(),
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

        $user = $leave_application->user;

        if (in_array($leave_application->leave_type, ['annual', 'personal'])) {
            $user->increment('leave_balance', $leave_application->leave_days);
        } elseif ($leave_application->leave_type === 'compensatory') {
            $user->increment('comp_off_balance', $leave_application->leave_days);
        }

        LeaveLog::create([
            'user_id' => $leave_application->user_id,
            'actor_id' => auth()->id(),
            'leave_application_id' => $leave_application->id,
            'action' => 'cancelled',
            'description' => 'User cancelled their pending leave request.',
        ]);

        $leave_application->delete();

        return redirect()->back()->with('success', 'Leave request canceled.');
    }

    public function uploadDocument(Request $request, LeaveApplication $leave_application)
    {
        if ($leave_application->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'supporting_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $path = $request->file('supporting_document')->store('leave_documents/' . auth()->id(), 'public');

        if ($leave_application->supporting_document_path) {
            Storage::disk('public')->delete($leave_application->supporting_document_path);
        }

        $leave_application->update([
            'supporting_document_path' => $path,
        ]);

        return redirect()->back()->with('success', 'Supporting document uploaded successfully.');
    }

    public function uploadDocumentInertia(Request $request, LeaveApplication $leave_application)
    {
        if ($leave_application->user_id !== auth()->id()) {
            abort(403);
        }

        if ($leave_application->leave_type !== 'sick') {
            return back()->with('error', 'Only sick leave allows document upload.');
        }

        $request->validate([
            'supporting_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $path = $request->file('supporting_document')->store('leave_documents/' . auth()->id(), 'public');

        if ($leave_application->supporting_document_path) {
            Storage::disk('public')->delete($leave_application->supporting_document_path);
        }

        $leave_application->update([
            'supporting_document_path' => $path,
        ]);

        $leave_application->refresh();

        return redirect()->route('leave.fullRequests')->with('success', 'Document uploaded successfully.');
    }

    private function notifyLeaveStatus(LeaveApplication $leaveApplication, string $status): void
    {
        try {
            $user = $leaveApplication->user;

            if ($status === 'approved') {
                $user->notify(new \App\Notifications\LeaveRequestApproved($leaveApplication));
            } elseif ($status === 'rejected') {
                $user->notify(new \App\Notifications\LeaveRequestRejected($leaveApplication));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send leave status notification', [
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

            foreach ($recipients as $email) {
                MailLog::create([
                    'leave_application_id' => $leaveApplication->id,
                    'recipient_email' => $email,
                    'subject' => $mailable->subject ?? 'Leave Application Notification',
                    'event_type' => $eventType,
                    'sent_at' => now(),
                    'reason' => $leaveApplication->reason,
                    'leave_period' => $leaveApplication->start_date->format('M d, Y') . ' to ' . $leaveApplication->end_date->format('M d, Y'),
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
                    'leave_period' => $leaveApplication->start_date->format('M d, Y') . ' to ' . $leaveApplication->end_date->format('M d, Y'),
                    'body_html' => $emailHtmlContent,
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }
        }
    }

    public function updateTemplateMapping(Request $request)
    {
        $request->validate([
            'event_type' => 'required|string',
            'mail_template_id' => 'required|string',
        ]);

        TemplateMapping::updateOrCreate(
            ['event_type' => $request->event_type],
            ['mail_template_id' => $request->mail_template_id]
        );

        return response()->json(['success' => true, 'message' => 'Template mapping updated successfully.']);
    }
}
