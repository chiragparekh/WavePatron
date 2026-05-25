<?php

namespace App\Http\Controllers;

use App\Contracts\ChecksCreatorProfile;
use App\Enums\AppMode;
use App\Enums\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        ChecksCreatorProfile $creatorProfiles,
    ): Response|RedirectResponse {
        $user = $request->user();

        if ($user->hasRole(Role::Admin->value)) {
            return redirect('/admin');
        }

        if ($user->activeAppMode() === AppMode::Creator) {
            if (! $creatorProfiles->hasProfile($user)) {
                return redirect(route('creator.onboarding', absolute: false));
            }

            return Inertia::render('creator/dashboard');
        }

        return Inertia::render('listener/dashboard');
    }
}
