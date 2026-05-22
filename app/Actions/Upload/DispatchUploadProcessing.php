<?php

namespace App\Actions\Upload;

use App\Enums\UploadStatus;
use App\Jobs\ProcessUploadMetadata;
use App\Models\Upload;
use Illuminate\Support\Facades\Bus;
use Throwable;

class DispatchUploadProcessing
{
    public function execute(Upload $upload): void
    {
        Bus::chain([
            new ProcessUploadMetadata($upload),
            // new ProcessUploadWaveform($upload),
            // new ProcessUploadHls($upload),
        ])->catch(function (Throwable $exception) use ($upload): void {
            $upload->update(['status' => UploadStatus::Failed]);
        })->dispatch();
    }
}
