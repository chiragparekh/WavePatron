<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Creator = 'creator';
    case Listener = 'listener';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
