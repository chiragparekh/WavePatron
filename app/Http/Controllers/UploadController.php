<?php

namespace App\Http\Controllers;

use App\Actions\Upload\ConfirmUpload;
use App\Actions\Upload\DispatchUploadProcessing;
use App\Actions\Upload\InitiateUpload;
use App\Http\Requests\StoreUploadRequest;
use App\Http\Resources\UploadResource;
use App\Models\Upload;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;

class UploadController extends Controller
{
    public function store(
        StoreUploadRequest $request,
        InitiateUpload $initiateUpload,
    ): JsonResponse {
        return response()->json(
            $initiateUpload->execute($request->user(), $request->validated()),
        );
    }

    #[Authorize('update', 'upload')]
    public function update(
        Upload $upload,
        ConfirmUpload $confirmUpload,
        DispatchUploadProcessing $dispatchUploadProcessing,
    ): UploadResource {
        $upload = $confirmUpload->execute($upload);

        $dispatchUploadProcessing->execute($upload);

        return new UploadResource($upload);
    }
}
