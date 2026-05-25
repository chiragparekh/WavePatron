<?php

namespace App\Http\Requests\Creator;

use App\Concerns\CreatorProfileValidationRules;
use App\Models\CreatorProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreCreatorProfileRequest extends FormRequest
{
    use CreatorProfileValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('create', CreatorProfile::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return $this->creatorProfileRules();
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('handle')) {
            $this->merge([
                'handle' => Str::slug(strtolower((string) $this->input('handle'))),
            ]);
        }

        $this->mergeCategoriesFromInput();
    }
}
