<?php

use App\Enums\StepStatus;
use App\Enums\UploadStatus;
use App\Enums\UploadStep;
use App\Jobs\Concerns\InteractsWithUploadStep;
use App\Models\Upload;
use App\Models\User;
use App\Notifications\UploadReadyNotification;
use Illuminate\Support\Facades\Notification;

test('upload ready notification is queued when the final step completes', function () {
    Notification::fake();

    $user = User::factory()->create();
    $upload = Upload::factory()->for($user)->processing()->withMetadata()->create([
        'step_statuses' => [
            UploadStep::Metadata->value => StepStatus::Completed->value,
            UploadStep::Waveform->value => StepStatus::Completed->value,
            UploadStep::Hls->value => StepStatus::Processing->value,
        ],
    ]);

    $job = new class($upload->uuid)
    {
        use InteractsWithUploadStep;

        public function __construct(public string $uploadUuid) {}

        protected function uploadStep(): UploadStep
        {
            return UploadStep::Hls;
        }

        public function complete(): void
        {
            $this->markStepCompleted($this->resolveUpload());
        }
    };

    $job->complete();

    $upload->refresh();

    expect($upload->status)->toBe(UploadStatus::Ready);

    Notification::assertSentTo($user, UploadReadyNotification::class);
});

test('upload ready notification is not sent when processing is incomplete', function () {
    Notification::fake();

    $user = User::factory()->create();
    $upload = Upload::factory()->for($user)->processing()->create([
        'step_statuses' => [
            UploadStep::Metadata->value => StepStatus::Completed->value,
            UploadStep::Waveform->value => StepStatus::Processing->value,
            UploadStep::Hls->value => StepStatus::Pending->value,
        ],
    ]);

    $job = new class($upload->uuid)
    {
        use InteractsWithUploadStep;

        public function __construct(public string $uploadUuid) {}

        protected function uploadStep(): UploadStep
        {
            return UploadStep::Metadata;
        }

        public function complete(): void
        {
            $this->markStepCompleted($this->resolveUpload());
        }
    };

    $job->complete();

    Notification::assertNothingSent();
});

test('upload ready notification includes audio details and audios link', function () {
    $user = User::factory()->create();
    $upload = Upload::factory()->for($user)->ready()->withMetadata()->create([
        'original_name' => 'episode.mp3',
    ]);

    $mail = (new UploadReadyNotification($upload))->toMail($user);

    expect($mail->subject)->toBe('Your audio "episode.mp3" is ready')
        ->and(collect($mail->introLines)->contains(fn (string $line): bool => str_contains($line, 'Duration: 01:25:10')))->toBeTrue()
        ->and(collect($mail->introLines)->contains(fn (string $line): bool => str_contains($line, 'Codec: mp3')))->toBeTrue()
        ->and($mail->actionUrl)->toBe(route('audios.index'));
});

test('upload ready notification omits missing metadata lines', function () {
    $user = User::factory()->create();
    $upload = Upload::factory()->for($user)->ready()->create([
        'original_name' => 'raw.wav',
    ]);

    $mail = (new UploadReadyNotification($upload))->toMail($user);

    expect(collect($mail->introLines)->contains(fn (string $line): bool => str_starts_with($line, 'Duration:')))->toBeFalse()
        ->and(collect($mail->introLines)->contains(fn (string $line): bool => str_starts_with($line, 'Codec:')))->toBeFalse();
});
