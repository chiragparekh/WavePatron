<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicTierResource;
use App\Models\CreatorProfile;
use Inertia\Inertia;
use Inertia\Response;

class CreatorProfileController extends Controller
{
    public function __invoke(string $handle): Response
    {
        $profile = CreatorProfile::query()
            ->where('handle', $handle)
            ->publiclyVisible()
            ->firstOrFail();

        $tiers = $profile->tiers()
            ->publiclyVisible()
            ->orderBy('monthly_price_cents')
            ->get();

        return Inertia::render('creators/show', [
            'profile' => $profile->toPublicArray(),
            'freeAudio' => [],
            'premiumAudio' => [],
            'tiers' => $tiers
                ->map(fn ($tier) => PublicTierResource::make($tier)->resolve())
                ->values()
                ->all(),
        ]);
    }
}
