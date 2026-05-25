<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasRole(Role::Admin->value)) {
            return redirect('/admin');
        }

        if ($user->hasRole(Role::Creator->value)) {
            return redirect()->route('creator.dashboard');
        }

        return redirect()->route('listener.dashboard');
    }
}
