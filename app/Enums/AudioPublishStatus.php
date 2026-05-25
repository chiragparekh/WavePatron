<?php

namespace App\Enums;

enum AudioPublishStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
