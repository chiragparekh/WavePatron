<?php

namespace App\Http\Controllers\Upload;

use App\Actions\Upload\ServeUploadWaveform;
use App\Http\Controllers\Controller;
use App\Models\Upload;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;

class WaveformController extends Controller
{
    #[Authorize('view', 'upload')]
    public function show(
        Upload $upload,
        ServeUploadWaveform $serveUploadWaveform,
    ): Response {
        return $serveUploadWaveform->execute($upload);
    }
}
