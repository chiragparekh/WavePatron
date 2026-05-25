<?php

namespace App\Http\Requests\Creator;

use App\Concerns\TierValidationRules;
use App\Models\Tier;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTierRequest extends FormRequest
{
    use TierValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Tier::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->tierRules();
    }
}
