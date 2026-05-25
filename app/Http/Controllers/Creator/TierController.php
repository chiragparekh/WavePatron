<?php

namespace App\Http\Controllers\Creator;

use App\Actions\Tier\ActivateTier;
use App\Actions\Tier\ArchiveTier;
use App\Actions\Tier\SaveTierDraft;
use App\Actions\Tier\SubmitTierRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\Creator\StoreTierRequest;
use App\Http\Requests\Creator\UpdateTierRequest;
use App\Http\Resources\CreatorTierListItemResource;
use App\Http\Resources\CreatorTierResource;
use App\Models\Tier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;
use Inertia\Response;

class TierController extends Controller
{
    public function index(Request $request): Response
    {
        $profile = $request->user()->creatorProfile()->firstOrFail();

        $tiers = $profile->tiers()
            ->latest()
            ->get();

        return Inertia::render('creator/tiers/index', [
            'tiers' => $tiers
                ->map(fn (Tier $tier) => CreatorTierListItemResource::make($tier)->resolve())
                ->values()
                ->all(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('creator/tiers/create');
    }

    public function store(
        StoreTierRequest $request,
        SaveTierDraft $saveTierDraft,
    ): RedirectResponse {
        $profile = $request->user()->creatorProfile()->firstOrFail();

        $tier = $saveTierDraft->execute(
            $profile,
            $request->user(),
            $request->tierAttributes(),
        );

        return to_route('creator.tiers.edit', $tier);
    }

    #[Authorize('view', 'tier')]
    public function edit(Tier $tier): Response
    {
        return Inertia::render('creator/tiers/edit', [
            'tier' => CreatorTierResource::make($tier)->resolve(),
        ]);
    }

    public function update(
        UpdateTierRequest $request,
        Tier $tier,
        SaveTierDraft $saveTierDraft,
    ): RedirectResponse {
        $profile = $request->user()->creatorProfile()->firstOrFail();

        $saveTierDraft->execute(
            $profile,
            $request->user(),
            $request->tierAttributes(),
            $tier,
        );

        return to_route('creator.tiers.edit', $tier);
    }

    #[Authorize('submit', 'tier')]
    public function submit(
        Request $request,
        Tier $tier,
        SubmitTierRequest $submitTierRequest,
    ): RedirectResponse {
        $submitTierRequest->execute($tier, $request->user());

        return to_route('creator.tiers.index');
    }

    #[Authorize('activate', 'tier')]
    public function activate(
        Request $request,
        Tier $tier,
        ActivateTier $activateTier,
    ): RedirectResponse {
        $activateTier->execute($tier, $request->user());

        return to_route('creator.tiers.index');
    }

    #[Authorize('archive', 'tier')]
    public function archive(
        Request $request,
        Tier $tier,
        ArchiveTier $archiveTier,
    ): RedirectResponse {
        $archiveTier->execute($tier, $request->user());

        return to_route('creator.tiers.index');
    }
}
