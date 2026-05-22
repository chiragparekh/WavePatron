<?php

namespace App\Enums;

enum UploadStatus: string
{
    case PendingUpload = 'pending_upload';
    case Uploaded = 'uploaded';
    case Processing = 'processing';
    case Ready = 'ready';
    case Failed = 'failed';
}
