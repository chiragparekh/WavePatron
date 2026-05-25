<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Active = 'active';
    case Grace = 'grace';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
}
