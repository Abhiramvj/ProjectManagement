<?php

namespace App\Actions\LeaveApplication;

use App\Models\LeaveApplication;
use Illuminate\Support\Facades\Storage;

class UploadLeaveDocumentAction
{
    public function handle(LeaveApplication $leaveApplication, $document): void
    {
        if ($leaveApplication->leave_type !== 'sick') {
            throw new \Exception('Only sick leave allows document upload.');
        }

        // Store new document
        $path = $document->store('leave_documents/'.$leaveApplication->user_id, 'public');

        // Delete old one if exists
        if ($leaveApplication->supporting_document_path) {
            Storage::disk('public')->delete($leaveApplication->supporting_document_path);
        }

        // Update record
        $leaveApplication->update([
            'supporting_document_path' => $path,
        ]);
    }
}
