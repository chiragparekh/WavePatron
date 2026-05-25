<?php

namespace App\Enums;

enum CreatorPayoutStatus: string
{
    case NotStarted = 'not_started';
    case Pending = 'pending';
    case Enabled = 'enabled';
    case Restricted = 'restricted';
}
