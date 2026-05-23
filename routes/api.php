<?php

use App\Http\Controllers\Api\UploadController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::apiResource('uploads', UploadController::class)->only(['store', 'show', 'update']);
});
