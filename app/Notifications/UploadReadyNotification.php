<?php

namespace App\Notifications;

use App\Models\Upload;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UploadReadyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Upload $upload,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $this->upload->loadMissing('metadata');

        $message = (new MailMessage)
            ->subject(sprintf('Your audio "%s" is ready', $this->upload->original_name))
            ->line('Processing has completed successfully.');

        if ($this->upload->metadata?->duration !== null) {
            $message->line(sprintf('Duration: %s', $this->upload->metadata->duration));
        }

        if ($this->upload->metadata?->codec !== null) {
            $message->line(sprintf('Codec: %s', $this->upload->metadata->codec));
        }

        return $message->action('Listen now', route('audios.index'));
    }
}
