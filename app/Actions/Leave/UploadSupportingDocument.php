<?php

namespace App\Actions\Leave;

use App\Models\LeaveApplication;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadSupportingDocument
{
    /**
     * Upload a supporting document for a leave application.
     *
     * @param  bool  $onlySick  If true, restrict to sick leave
     * @return string Path of stored file
     *
     * @throws \Exception
     */
    public function handle(LeaveApplication $leaveApplication, UploadedFile $file, bool $onlySick = false): string
    {
        if ($onlySick && $leaveApplication->leave_type !== 'sick') {
            throw new \Exception('Only sick leave allows document upload.');
        }

        $userId = $leaveApplication->user_id;

        // Store file in user-specific folder
        $path = $file->store("leave_documents/{$userId}", 'public');

        // Delete old document if exists
        if ($leaveApplication->supporting_document_path) {
            Storage::disk('public')->delete($leaveApplication->supporting_document_path);
        }

        // Update leave application
        $leaveApplication->update(['supporting_document_path' => $path]);
        $leaveApplication->refresh();

        return $path;
    }
}
