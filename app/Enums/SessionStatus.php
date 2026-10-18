<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SessionStatus: string implements HasColor, HasLabel
{
    case Ongoing = 'ongoing';
    case Ended = 'ended';
    case Upcoming = 'upcoming';

    public function getLabel(): string
    {
        return __("democratic_time_session.status.{$this->value}");
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Ongoing => 'success',
            self::Ended => 'gray',
            self::Upcoming => 'info',
        };
    }
}
