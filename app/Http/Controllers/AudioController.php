<?php

namespace App\Http\Controllers;

use App\Http\Resources\UploadListItemResource;
use App\Http\Resources\UploadProcessingItemResource;
use App\Queries\Upload\ProcessingUploadsQuery;
use App\Queries\Upload\ReadyUploadsQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AudioController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('audios/index', [
            'uploads' => Inertia::scroll(
                UploadListItemResource::collection(
                    (new ReadyUploadsQuery($user))->builder()->paginate(20),
                ),
            ),
            'processingUploads' => Inertia::optional(fn () => UploadProcessingItemResource::collection(
                (new ProcessingUploadsQuery($user))->builder()->get(),
            )->resolve()),
        ]);
    }
}
