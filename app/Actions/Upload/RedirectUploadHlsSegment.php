<?php

namespace App\Actions\Upload;

use App\Models\Upload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class RedirectUploadHlsSegment
{
    public function execute(Upload $upload, string $segment): RedirectResponse
    {
        if ($upload->hls_path === null) {
            abort(404);
        }

        if (! preg_match('/^segment_\d+\.ts$/', $segment)) {
            abort(404);
        }

        $segmentPath = $upload->hlsSegmentPath($segment);

        if (! Storage::disk($upload->disk)->exists($segmentPath)) {
            abort(404);
        }

        $url = Storage::disk($upload->disk)->temporaryUrl(
            $segmentPath,
            now()->addMinutes(5),
        );

        return redirect()->away($url);
    }
}
