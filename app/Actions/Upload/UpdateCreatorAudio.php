<?php

namespace App\Actions\Upload;

use App\Enums\AudioAccessLevel;
use App\Enums\AudioPublishStatus;
use App\Models\Upload;
use App\Models\User;

class UpdateCreatorAudio
{
    public function __construct(private LogUploadActivity $logUploadActivity) {}

    /**
     * @param  array{title?: ?string, description?: ?string, publish_status?: AudioPublishStatus, access_level?: AudioAccessLevel}  $attributes
     */
    public function execute(Upload $upload, User $actor, array $attributes): Upload
    {
        $changes = [];

        if (array_key_exists('title', $attributes) && $attributes['title'] !== $upload->title) {
            $changes['title'] = ['from' => $upload->title, 'to' => $attributes['title']];
            $upload->title = $attributes['title'];
        }

        if (array_key_exists('description', $attributes) && $attributes['description'] !== $upload->description) {
            $changes['description'] = ['from' => $upload->description, 'to' => $attributes['description']];
            $upload->description = $attributes['description'];
        }

        if (array_key_exists('publish_status', $attributes) && $attributes['publish_status'] !== $upload->publish_status) {
            $changes['publish_status'] = [
                'from' => $upload->publish_status->value,
                'to' => $attributes['publish_status']->value,
            ];
            $upload->publish_status = $attributes['publish_status'];
        }

        if (array_key_exists('access_level', $attributes) && $attributes['access_level'] !== $upload->access_level) {
            $changes['access_level'] = [
                'from' => $upload->access_level->value,
                'to' => $attributes['access_level']->value,
            ];
            $upload->access_level = $attributes['access_level'];
        }

        if ($changes === []) {
            return $upload;
        }

        $upload->save();

        if (isset($changes['publish_status'])) {
            $event = $upload->isPublished() ? 'published' : 'unpublished';
            $this->logUploadActivity->execute($upload, $event, $actor, $changes);
        }

        if (isset($changes['access_level'])) {
            $this->logUploadActivity->execute($upload, 'access_level_changed', $actor, $changes);
        }

        if (isset($changes['title']) || isset($changes['description'])) {
            $this->logUploadActivity->execute($upload, 'metadata_updated', $actor, $changes);
        }

        return $upload->fresh(['metadata']);
    }
}
