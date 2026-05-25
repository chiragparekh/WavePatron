<?php

namespace App\Jobs\Concerns;

use App\Enums\StepStatus;
use App\Enums\UploadStatus;
use App\Enums\UploadStep;
use App\Models\Upload;
use App\Notifications\UploadReadyNotification;
use Illuminate\Support\Facades\Log;
use Throwable;

trait InteractsWithUploadStep
{
    abstract protected function uploadStep(): UploadStep;

    protected function resolveUpload(): Upload
    {
        return Upload::query()
            ->where('uuid', $this->uploadUuid)
            ->firstOrFail();
    }

    protected function markStepProcessing(Upload $upload): void
    {
        $this->updateStepStatus($upload, StepStatus::Processing);
        $upload->update(['status' => UploadStatus::Processing]);
    }

    protected function markStepCompleted(Upload $upload): void
    {
        $this->updateStepStatus($upload, StepStatus::Completed);
        $this->markReadyIfComplete($upload);
    }

    protected function markStepFailed(Upload $upload): void
    {
        $this->updateStepStatus($upload, StepStatus::Failed);
        $upload->update(['status' => UploadStatus::Failed]);
    }

    protected function safelyHandleFailure(?Throwable $exception, string $message): void
    {
        try {
            $upload = $this->resolveUpload();

            Log::error($message, $this->logContext($upload, [
                'exception' => $exception?->getMessage(),
            ]));

            $this->markStepFailed($upload);
        } catch (Throwable $secondaryException) {
            Log::error('Could not mark upload step as failed.', [
                'upload_uuid' => $this->uploadUuid,
                'step' => $this->uploadStep()->value,
                'original_exception' => $exception?->getMessage(),
                'secondary_exception' => $secondaryException->getMessage(),
            ]);
        }
    }

    protected function markReadyIfComplete(Upload $upload): void
    {
        $upload->refresh();

        $allStepsComplete = collect(UploadStep::cases())
            ->every(function (UploadStep $step) use ($upload): bool {
                $status = $upload->step_statuses[$step->value] ?? StepStatus::Pending->value;

                return in_array($status, [
                    StepStatus::Completed->value,
                    StepStatus::Skipped->value,
                ], true);
            });

        if ($allStepsComplete) {
            $upload->update(['status' => UploadStatus::Ready]);

            $upload->load('user', 'metadata');
            $upload->user->notify(new UploadReadyNotification($upload));
        }
    }

    protected function updateStepStatus(Upload $upload, StepStatus $status): void
    {
        $stepStatuses = $upload->step_statuses;
        $stepStatuses[$this->uploadStep()->value] = $status->value;

        $upload->update(['step_statuses' => $stepStatuses]);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    protected function logContext(Upload $upload, array $context = []): array
    {
        return array_merge([
            'upload_id' => $upload->id,
            'upload_uuid' => $upload->uuid,
            'user_id' => $upload->user_id,
            'step' => $this->uploadStep()->value,
        ], $context);
    }
}
