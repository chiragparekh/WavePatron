<?php

namespace App\Enums;

enum CreatorProfileVisibility: string
{
    case Draft = 'draft';
    case Public = 'public';
    case Hidden = 'hidden';
}
