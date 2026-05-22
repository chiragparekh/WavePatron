<?php

namespace App\Enums;

enum UploadStep: string
{
    case Metadata = 'metadata';
    case Waveform = 'waveform';
    case Hls = 'hls';
}
