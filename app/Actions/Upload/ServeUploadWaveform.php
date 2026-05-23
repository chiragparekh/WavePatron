<?php

namespace App\Actions\Upload;

use App\Models\Upload;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class ServeUploadWaveform
{
    public function execute(Upload $upload): Response
    {
        if ($upload->waveform_path === null) {
            abort(404);
        }

        $waveform = Storage::disk($upload->disk)->get($upload->waveform_path);

        return response($waveform, 200, [
            'Content-Type' => 'application/json',
        ]);
    }
}
