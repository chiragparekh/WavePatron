<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreUploadRequest extends FormRequest
{
    public const MAX_UPLOAD_BYTES = 524_288_000;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'size' => ['required', 'integer', 'min:1', 'max:'.self::MAX_UPLOAD_BYTES],
            'type' => ['required', 'string', 'starts_with:audio/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.starts_with' => 'The file must be an audio file.',
            'size.max' => 'The file may not be larger than 500 MB.',
        ];
    }
}
