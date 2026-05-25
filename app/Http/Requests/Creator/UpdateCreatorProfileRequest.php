<?php

namespace App\Http\Requests\Creator;

use App\Concerns\CreatorProfileValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class UpdateCreatorProfileRequest extends FormRequest
{
    use CreatorProfileValidationRules;

    public function authorize(): bool
    {
        $profile = $this->user()?->creatorProfile;

        return $profile !== null && $this->user()->can('update', $profile);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return $this->creatorProfileRules($this->user()->creatorProfile);
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
