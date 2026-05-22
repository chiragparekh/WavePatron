<?php

namespace App\Actions\Upload;

use App\Enums\UploadStatus;
use App\Models\Upload;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ConfirmUpload
{
    public function execute(Upload $upload): Upload
    {
        if ($upload->status !== UploadStatus::PendingUpload) {
            throw ValidationException::withMessages([
                'upload' => 'This upload has already been confirmed.',
            ]);
        }

        if (! Storage::disk($upload->disk)->exists($upload->path)) {
            throw ValidationException::withMessages([
                'upload' => 'The uploaded file was not found on storage.',
            ]);
        }

        $upload->update([
            'status' => UploadStatus::Uploaded,
            'uploaded_at' => now(),
        ]);

        return $upload->fresh();
    }
}
