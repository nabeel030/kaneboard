<?php

namespace App\Enums;

enum TicketType: string
{
    case BUG = 'bug';
    case FEATURE = 'feature';
    case IMPROVEMENT = 'improvement';

    public function label(): string
    {
        return match ($this) {
            self::BUG => 'Bug',
            self::FEATURE => 'Feature',
            self::IMPROVEMENT => 'Improvement',
        };
    }
}
