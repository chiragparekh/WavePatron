<?php

namespace App\Http\Controllers\Upload;

use App\Http\Controllers\Controller;
use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;

class CreateUploadController extends Controller
{
    #[Authorize('create', Upload::class)]
    public function __invoke(Request $request): Response
    {
        return Inertia::render('uploads/create');
    }
}
