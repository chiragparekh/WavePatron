<?php

namespace App\Http\Controllers\Api;

use App\Actions\Upload\ConfirmUpload;
use App\Actions\Upload\DispatchUploadProcessing;
use App\Actions\Upload\InitiateUpload;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUploadRequest;
use App\Http\Resources\SignedUploadResource;
use App\Http\Resources\UploadResource;
use App\Models\Upload;
use Illuminate\Routing\Attributes\Controllers\Authorize;

class UploadController extends Controller
{
    public function store(
        StoreUploadRequest $request,
        InitiateUpload $initiateUpload,
    ): SignedUploadResource {
        return new SignedUploadResource(
            $initiateUpload->execute($request->user(), $request->validated()),
        );
    }

    #[Authorize('view', 'upload')]
    public function show(Upload $upload): UploadResource
    {
        return new UploadResource($upload);
    }

    #[Authorize('update', 'upload')]
    public function update(
        Upload $upload,
        ConfirmUpload $confirmUpload,
        DispatchUploadProcessing $dispatchUploadProcessing,
    ): UploadResource {
        $upload = $confirmUpload->execute($upload);

        $dispatchUploadProcessing->execute($upload);

        return new UploadResource($upload->fresh());
    }
}
