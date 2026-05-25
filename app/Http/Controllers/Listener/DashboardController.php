<?php

namespace App\Http\Controllers\Listener;

use App\Http\Controllers\Controller;
use App\Queries\Upload\UploadStatsQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('listener/dashboard', [
            'stats' => (new UploadStatsQuery($request->user()))->get(),
        ]);
    }
}
