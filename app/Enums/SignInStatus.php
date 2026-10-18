<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SignInStatus: string implements HasColor, HasLabel
{
    case Open = 'open';
    case Closed = 'closed';
    case NotOpened = 'not_opened';

    public function getLabel(): string
    {
        return __("emargement.status.{$this->value}");
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Open => 'success',
            self::Closed => 'gray',
            self::NotOpened => 'warning',
        };
    }
}
