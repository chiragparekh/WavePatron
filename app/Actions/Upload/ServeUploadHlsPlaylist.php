<?php

namespace App\Actions\Upload;

use App\Models\Upload;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class ServeUploadHlsPlaylist
{
    public function execute(Upload $upload): Response
    {
        if ($upload->hls_path === null) {
            abort(404);
        }

        $manifest = Storage::disk($upload->disk)->get($upload->hls_path);

        return response($manifest, 200, [
            'Content-Type' => 'application/vnd.apple.mpegurl',
        ]);
    }
}
