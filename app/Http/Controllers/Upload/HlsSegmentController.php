<?php

namespace App\Http\Controllers\Upload;

use App\Actions\Upload\RedirectUploadHlsSegment;
use App\Http\Controllers\Controller;
use App\Models\Upload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;

class HlsSegmentController extends Controller
{
    #[Authorize('view', 'upload')]
    public function show(
        Upload $upload,
        string $segment,
        RedirectUploadHlsSegment $redirectUploadHlsSegment,
    ): RedirectResponse {
        return $redirectUploadHlsSegment->execute($upload, $segment);
    }
}
