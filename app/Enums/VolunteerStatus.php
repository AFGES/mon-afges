<?php

declare(strict_types=1);

namespace App\Enums;

enum VolunteerStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('volunteer.status.pending'),
            self::Approved => __('volunteer.status.approved'),
            self::Rejected => __('volunteer.status.rejected'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }
}
