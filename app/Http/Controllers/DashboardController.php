<?php

namespace App\Http\Controllers;

use App\Support\AuthRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, AuthRedirect $authRedirect): RedirectResponse
    {
        return redirect($authRedirect->homeUrl($request->user()));
    }
}
