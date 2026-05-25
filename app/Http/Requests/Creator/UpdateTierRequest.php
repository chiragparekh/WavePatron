<?php

namespace App\Http\Requests\Creator;

use App\Concerns\TierValidationRules;
use App\Models\Tier;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTierRequest extends FormRequest
{
    use TierValidationRules;

    public function authorize(): bool
    {
        /** @var Tier $tier */
        $tier = $this->route('tier');

        return $this->user()?->can('update', $tier) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->tierRules();
    }
}
