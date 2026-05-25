<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Creator\StoreCreatorProfileRequest;
use App\Http\Requests\Creator\UpdateCreatorProfileRequest;
use App\Models\CreatorProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response|RedirectResponse
    {
        $profile = $request->user()->creatorProfile;

        if ($profile === null) {
            return redirect()->route('creator.onboarding');
        }

        return Inertia::render('creator/profile/edit', [
            'profile' => $this->profileFormData($profile),
        ]);
    }

    public function store(StoreCreatorProfileRequest $request): RedirectResponse
    {
        $profile = CreatorProfile::query()->create([
            ...$request->safe()->except(['avatar', 'cover_image']),
            'user_id' => $request->user()->id,
        ]);

        $this->syncMedia($profile, $request);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Creator profile created.')]);

        return redirect()->route('dashboard');
    }

    public function update(UpdateCreatorProfileRequest $request): RedirectResponse
    {
        $profile = $request->user()->creatorProfile;

        $profile->update($request->safe()->except(['avatar', 'cover_image']));

        $this->syncMedia($profile, $request);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Creator profile updated.')]);

        return redirect()->route('creator.profile.edit');
    }

    /**
     * @return array<string, mixed>
     */
    private function profileFormData(CreatorProfile $profile): array
    {
        return [
            'handle' => $profile->handle,
            'display_name' => $profile->display_name,
            'tagline' => $profile->tagline,
            'bio' => $profile->bio,
            'categories' => $profile->categories ?? [],
            'website' => $profile->website,
            'social_links' => $profile->social_links ?? [],
            'support_email' => $profile->support_email,
            'visibility' => $profile->visibility->value,
            'avatar_url' => $profile->avatarUrl(),
            'cover_image_url' => $profile->coverImageUrl(),
        ];
    }

    private function syncMedia(CreatorProfile $profile, Request $request): void
    {
        $updates = [];

        if ($request->hasFile('avatar')) {
            if ($profile->avatar_path !== null) {
                Storage::disk('public')->delete($profile->avatar_path);
            }

            $updates['avatar_path'] = $request->file('avatar')->store(
                "creator-profiles/{$profile->id}",
                'public',
            );
        }

        if ($request->hasFile('cover_image')) {
            if ($profile->cover_image_path !== null) {
                Storage::disk('public')->delete($profile->cover_image_path);
            }

            $updates['cover_image_path'] = $request->file('cover_image')->store(
                "creator-profiles/{$profile->id}",
                'public',
            );
        }

        if ($updates !== []) {
            $profile->update($updates);
        }
    }
}
