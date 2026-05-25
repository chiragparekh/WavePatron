<?php

namespace App\Http\Controllers;

use App\Queries\Upload\UploadStatsQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('dashboard', [
            'stats' => (new UploadStatsQuery($request->user()))->get(),
        ]);
    }
}
