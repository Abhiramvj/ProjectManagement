<?php

namespace App\Http\Controllers;

use App\Actions\LeaveApplication\ApproveCompOffAction;
use App\Actions\LeaveApplication\CancelLeaveApplicationAction;
use App\Actions\LeaveApplication\GetLeaveApplicationAction;
use App\Actions\LeaveApplication\ProcessLeaveApplicationAction;
use App\Actions\LeaveApplication\StoreLeaveApplicationAction;
use App\Actions\LeaveApplication\UpdateLeaveReasonAction;
use App\Actions\LeaveApplication\UpdateTemplateMappingAction;
use App\Actions\LeaveApplication\UploadLeaveDocumentAction;
use App\Http\Requests\LeaveApplication\ApproveCompOffRequest;
use App\Http\Requests\LeaveApplication\StoreLeaveRequest;
use App\Http\Requests\LeaveApplication\UpdateLeaveReasonRequest;
use App\Http\Requests\LeaveApplication\UpdateLeaveRequest;
use App\Http\Requests\LeaveApplication\UpdateTemplateMappingRequest;
use App\Http\Requests\LeaveApplication\UploadLeaveDocumentRequest;
use App\Models\LeaveApplication;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class LeaveApplicationController extends Controller
{
    use AuthorizesRequests;

    public function index(GetLeaveApplicationAction $getLeaveRequests)
    {
        return Inertia::render('Leave/Index', $getLeaveRequests->execute());
    }

    public function store(StoreLeaveRequest $request, StoreLeaveApplicationAction $storeLeave)
    {

        // Everything else is handled inside the action
        $storeLeave->execute($request->validated());

        return Redirect::route('leave.index')
            ->with('success', 'Leave application submitted successfully.');

    }

    public function approveCompOff(ApproveCompOffRequest $request, User $user, ApproveCompOffAction $approveCompOff)
    {
        $daysToCredit = (float) $request->input('comp_off_days');
        $reason = $request->input('reason');

        $approveCompOff->execute($user, $daysToCredit, $reason);

        return redirect()->back()->with('success', "Compensatory off of {$daysToCredit} days credited to user.");
    }

    public function update(UpdateLeaveRequest $request, LeaveApplication $leaveApplication, ProcessLeaveApplicationAction $processLeaveApplication)
    {
        $validated = $request->validated();
        $status = $validated['status'];
        $rejectReason = $validated['rejection_reason'] ?? null;

        $processLeaveApplication->handle($leaveApplication, $status, $rejectReason);

        return redirect()->back()->with('success', 'Application status updated.');
    }

    public function updateReason(UpdateLeaveReasonRequest $request, LeaveApplication $leave_application, UpdateLeaveReasonAction $updateLeaveReason)
    {
        $updateLeaveReason->handle($leave_application, $request->validated()['reason']);

        return back()->with('success', 'Reason updated.');
    }

    public function cancel(LeaveApplication $leaveApplication, CancelLeaveApplicationAction $cancelLeave)
    {
        $cancelLeave->handle($leaveApplication);

        return back()->with('success', 'Leave request canceled.');
    }

    public function uploadDocumentInertia(UploadLeaveDocumentRequest $request, LeaveApplication $leaveApplication, UploadLeaveDocumentAction $uploadLeaveDocument)
    {
        $uploadLeaveDocument->handle($leaveApplication, $request->file('supporting_document'));

        return redirect()->route('leave.fullRequests')->with('success', 'Document uploaded successfully.');
    }

    public function updateTemplateMapping(UpdateTemplateMappingRequest $request, UpdateTemplateMappingAction $updateTemplateMapping)
    {
        $validated = $request->validated();

        $updateTemplateMapping->handle(
            $validated['event_type'],
            $validated['mail_template_id']
        );

        return response()->json([
            'success' => true,
            'message' => 'Template mapping updated successfully.',
        ]);
    }
}
