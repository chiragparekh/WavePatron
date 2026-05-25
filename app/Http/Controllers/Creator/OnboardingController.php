<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        if ($request->user()->creatorProfile !== null) {
            return redirect()->route('creator.profile.edit');
        }

        return Inertia::render('creator/onboarding', [
            'suggestedHandle' => Str::slug($request->user()->name),
        ]);
    }
}
