<?php

namespace App\Http\Requests\Account;

use App\Enums\AppMode;
use App\Enums\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAccountModeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mode' => ['required', Rule::enum(AppMode::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->user();

            if ($user === null) {
                return;
            }

            $mode = AppMode::tryFrom((string) $this->input('mode'));

            if ($mode === null) {
                return;
            }

            $requiredRole = match ($mode) {
                AppMode::Listener => Role::Listener,
                AppMode::Creator => Role::Creator,
            };

            if (! $user->hasRole($requiredRole->value)) {
                $validator->errors()->add('mode', 'You are not allowed to use this account mode.');
            }
        });
    }
}
