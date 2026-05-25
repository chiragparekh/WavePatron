<?php

namespace App\Enums;

enum TierStatus: string
{
    case Draft = 'draft';
    case Requested = 'requested';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Active = 'active';
    case Archived = 'archived';
}
