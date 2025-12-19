<?php

declare(strict_types=1);

namespace App\Enums;

enum DelegateStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('delegate.status.pending'),
            self::Approved => __('delegate.status.approved'),
            self::Rejected => __('delegate.status.rejected'),
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
