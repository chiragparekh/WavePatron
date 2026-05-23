<?php

namespace App\Http\Controllers;

use App\Enums\UploadStatus;
use App\Http\Resources\UploadListItemResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AudioController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('audios/index', [
            'uploads' => Inertia::scroll(
                UploadListItemResource::collection(
                    $request->user()
                        ->uploads()
                        ->with('metadata')
                        ->where('status', UploadStatus::Ready)
                        ->latest('uploaded_at')
                        ->paginate(20),
                ),
            ),
        ]);
    }
}
