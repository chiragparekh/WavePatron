<?php

namespace App\Concerns;

use App\Enums\CreatorProfileVisibility;
use App\Models\CreatorProfile;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait CreatorProfileValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function creatorProfileRules(?CreatorProfile $profile = null): array
    {
        return [
            'handle' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/',
                $profile === null
                    ? Rule::unique(CreatorProfile::class, 'handle')
                    : Rule::unique(CreatorProfile::class, 'handle')->ignore($profile),
            ],
            'display_name' => ['required', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:5000'],
            'categories' => ['nullable', 'array', 'max:10'],
            'categories.*' => ['string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'social_links' => ['nullable', 'array'],
            'social_links.*' => ['nullable', 'url', 'max:255'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'visibility' => ['required', Rule::enum(CreatorProfileVisibility::class)],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'cover_image' => ['nullable', 'image', 'max:5120'],
        ];
    }

    protected function mergeCategoriesFromInput(): void
    {
        $categories = $this->input('categories');

        if (! is_string($categories)) {
            return;
        }

        $parsed = array_values(array_filter(array_map(
            trim(...),
            explode(',', $categories),
        )));

        $this->merge([
            'categories' => $parsed === [] ? null : $parsed,
        ]);
    }
}
