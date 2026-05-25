<?php

namespace App\Http\Controllers\Creator;

use App\Actions\Upload\UpdateCreatorAudio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Creator\UpdateCreatorAudioRequest;
use App\Http\Resources\CreatorAudioListItemResource;
use App\Http\Resources\CreatorAudioResource;
use App\Models\Upload;
use App\Queries\Upload\CreatorUploadsQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;

class AudioController extends Controller
{
    public function index(Request $request): Response
    {
        $uploads = (new CreatorUploadsQuery($request->user()))->builder()->paginate(20);

        return Inertia::render('creator/audios/index', [
            'uploads' => CreatorAudioListItemResource::collection($uploads),
        ]);
    }

    #[Authorize('update', 'upload')]
    public function edit(Request $request, Upload $upload): Response
    {
        $upload->load('metadata');

        return Inertia::render('creator/audios/edit', [
            'upload' => CreatorAudioResource::make($upload)->resolve(),
        ]);
    }

    public function update(
        UpdateCreatorAudioRequest $request,
        Upload $upload,
        UpdateCreatorAudio $updateCreatorAudio,
    ): RedirectResponse {
        $updateCreatorAudio->execute(
            $upload,
            $request->user(),
            $request->creatorAudioAttributes(),
        );

        return to_route('creator.audios.edit', $upload);
    }
}
