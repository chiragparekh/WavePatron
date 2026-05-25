<?php

namespace App\Actions\Upload;

use App\Actions\Activity\LogAppActivity;
use App\Models\Upload;
use App\Models\User;

class LogUploadActivity
{
    public function __construct(private LogAppActivity $logAppActivity) {}

    /**
     * @param  array<string, mixed>  $properties
     */
    public function execute(Upload $upload, string $event, ?User $causer = null, array $properties = []): void
    {
        $this->logAppActivity->execute(
            event: $event,
            subject: $upload,
            causer: $causer,
            properties: $properties,
            logName: 'upload',
        );
    }
}
