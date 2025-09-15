<?php

namespace App\Actions\LeaveApplication;

use Illuminate\Http\UploadedFile;

class LeaveDocumentUploader
{
    public function upload($user, ?UploadedFile $document): ?string
    {
        if (! $document) {
            return null;
        }

        return $document->store("leave_documents/{$user->id}", 'public');
    }
}
