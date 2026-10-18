<?php

declare(strict_types=1);

namespace App\Enums;

enum QuorumMode: string
{
    case Combined = 'combined';
    case PerCollege = 'per_college';
}
