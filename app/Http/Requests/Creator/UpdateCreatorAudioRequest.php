<?php

namespace App\Http\Requests\Creator;

use App\Enums\AudioAccessLevel;
use App\Enums\AudioPublishStatus;
use App\Enums\UploadStatus;
use App\Models\Upload;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCreatorAudioRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Upload $upload */
        $upload = $this->route('upload');

        return $this->user()?->can('update', $upload) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'publish_status' => ['sometimes', Rule::enum(AudioPublishStatus::class)],
            'access_level' => ['sometimes', Rule::enum(AudioAccessLevel::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->filled('publish_status')) {
                return;
            }

            /** @var Upload $upload */
            $upload = $this->route('upload');

            if (
                $this->enum('publish_status', AudioPublishStatus::class) === AudioPublishStatus::Published
                && $upload->status !== UploadStatus::Ready
            ) {
                $validator->errors()->add(
                    'publish_status',
                    'Audio must finish processing before it can be published.',
                );
            }
        });
    }

    /**
     * @return array{title?: ?string, description?: ?string, publish_status?: AudioPublishStatus, access_level?: AudioAccessLevel}
     */
    public function creatorAudioAttributes(): array
    {
        $attributes = [];

        if ($this->has('title')) {
            $attributes['title'] = $this->validated('title');
        }

        if ($this->has('description')) {
            $attributes['description'] = $this->validated('description');
        }

        if ($this->has('publish_status')) {
            $attributes['publish_status'] = $this->enum('publish_status', AudioPublishStatus::class);
        }

        if ($this->has('access_level')) {
            $attributes['access_level'] = $this->enum('access_level', AudioAccessLevel::class);
        }

        return $attributes;
    }
}
