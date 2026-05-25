<?php

namespace App\Enums;

enum AppMode: string
{
    case Listener = 'listener';
    case Creator = 'creator';
}
