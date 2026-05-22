<?php

namespace App\Jobs\Concerns;

use App\Enums\StepStatus;
use App\Enums\UploadStatus;
use App\Enums\UploadStep;
use App\Models\Upload;

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
        }
    }

    protected function updateStepStatus(Upload $upload, StepStatus $status): void
    {
        $stepStatuses = $upload->step_statuses;
        $stepStatuses[$this->uploadStep()->value] = $status->value;

        $upload->update(['step_statuses' => $stepStatuses]);
    }
}
