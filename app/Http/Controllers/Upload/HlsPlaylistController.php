<?php

namespace App\Http\Controllers\Upload;

use App\Actions\Upload\ServeUploadHlsPlaylist;
use App\Http\Controllers\Controller;
use App\Models\Upload;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;

class HlsPlaylistController extends Controller
{
    #[Authorize('view', 'upload')]
    public function show(
        Upload $upload,
        ServeUploadHlsPlaylist $serveUploadHlsPlaylist,
    ): Response {
        return $serveUploadHlsPlaylist->execute($upload);
    }
}
