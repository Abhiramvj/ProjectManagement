<?php

namespace App\Http\Controllers;

use App\Actions\Leave\ApproveLeave;
use App\Actions\Leave\GetLeave;
use App\Actions\Leave\RejectLeave;
use App\Actions\Leave\SendLeaveEmail;
use App\Actions\Leave\StoreLeave;
use App\Actions\Leave\UpdateLeave;
use App\Actions\Leave\UploadSupportingDocument;
use App\Http\Requests\Leave\StoreLeaveRequest;
use App\Http\Requests\Leave\UpdateLeaveRequest;
use App\Models\LeaveApplication;
use App\Models\User;
use App\Services\LeaveLogger;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class LeaveApplicationController extends Controller
{
    use AuthorizesRequests;

    // --- INDEX ---
    public function index(GetLeave $getLeaveRequests)
    {
        return Inertia::render('Leave/Index', $getLeaveRequests->handle());
    }

    // --- STORE NEW LEAVE ---
    public function store(
        StoreLeaveRequest $request,
        StoreLeave $storeLeave,
        LeaveLogger $logger,
        SendLeaveEmail $mailer
    ) {
        try {
            $leave_application = $storeLeave->handle($request->validated());

            // Log submission
            $logger->handle(
                $leave_application,
                'created',
                'Submitted a new leave request for '.$leave_application->leave_days.' day(s).',
                [
                    'leave_type' => $leave_application->leave_type,
                    'start_date' => $leave_application->start_date->toDateString(),
                    'end_date' => $leave_application->end_date->toDateString(),
                ]
            );

            // Notify HR/Admin
            $recipients = User::role(['admin', 'hr'])->pluck('email')->toArray();
            if (! empty($recipients)) {
                $mailer->handle($leave_application, new \App\Mail\LeaveApplicationSubmitted($leave_application), $recipients);
            }

            return Redirect::route('leave.index')->with('success', 'Leave application submitted successfully.');
        } catch (\Exception $e) {
            report($e);

            return Redirect::back()->with('error', 'Server error. Try again later.');
        }
    }

    // --- UPDATE LEAVE STATUS ---
   public function update(
    UpdateLeaveRequest $request,
    LeaveApplication $leave_application,
    ApproveLeave $approveLeave,
    RejectLeave $rejectLeave
) {
    $validated = $request->validated();
    $status = $validated['status'];
    $rejectReason = $validated['rejection_reason'] ?? 'No reason provided.';

    $updateLeave = app(UpdateLeave::class); 

    if ($status === 'approved') {
        $approveLeave->handle($leave_application, $updateLeave);
    } elseif ($status === 'rejected') {
        $rejectLeave->handle($leave_application, $updateLeave, $rejectReason);
    }

    return redirect()->back()->with('success', 'Application status updated.');
}


    // --- CANCEL LEAVE ---
    public function cancel(LeaveApplication $leave_application, LeaveLogger $logger)
    {
        if ($leave_application->user_id !== auth()->id() || $leave_application->status !== 'pending') {
            abort(403, 'Unauthorized action.');
        }

        $balanceColumn = $leave_application->getBalanceColumn();
        if ($balanceColumn) {
            $leave_application->user->increment($balanceColumn, $leave_application->leave_days);
        }

        $logger->handle($leave_application, 'cancelled', 'User cancelled their pending leave request.');

        $leave_application->delete();

        return Redirect::back()->with('success', 'Leave request canceled.');
    }

    // --- UPDATE REASON ---
    public function updateReason(Request $request, LeaveApplication $leave_application)
    {
        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $leave_application->update(['reason' => $validated['reason']]);

        return Redirect::back()->with('success', 'Reason updated.');
    }

    // --- DOCUMENT UPLOAD ---
    public function uploadDocument(Request $request, LeaveApplication $leave_application, UploadSupportingDocument $uploader)
    {
        $this->authorize('update', $leave_application);

        $request->validate([
            'supporting_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        try {
            $uploader->handle($leave_application, $request->file('supporting_document'));

            return Redirect::back()->with('success', 'Supporting document uploaded successfully.');
        } catch (\Exception $e) {
            return Redirect::back()->with('error', $e->getMessage());
        }
    }

    public function uploadDocumentInertia(Request $request, LeaveApplication $leave_application, UploadSupportingDocument $uploader)
    {
        $this->authorize('update', $leave_application);

        $request->validate([
            'supporting_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        try {
            $uploader->handle($leave_application, $request->file('supporting_document'), true);

            return Redirect::route('leave.fullRequests')->with('success', 'Document uploaded successfully.');
        } catch (\Exception $e) {
            return Redirect::back()->with('error', $e->getMessage());
        }
    }
}
