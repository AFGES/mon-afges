<?php

declare(strict_types=1);

namespace App\Traits;

use App\Enums\ApprovalStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasApprovalStatus
{
    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function isPending(): bool
    {
        return $this->status === ApprovalStatus::Pending;
    }

    public function isApproved(): bool
    {
        return $this->status === ApprovalStatus::Approved;
    }

    public function isRejected(): bool
    {
        return $this->status === ApprovalStatus::Rejected;
    }

    public function approve(?int $approvedBy = null): bool
    {
        if ($this->isApproved()) {
            return false;
        }

        $this->update([
            'status' => ApprovalStatus::Approved,
            'approved_at' => now(),
            'approved_by' => $approvedBy ?? auth()->id(),
            'rejection_reason' => null,
        ]);

        return true;
    }

    public function reject(string $reason, ?int $rejectedBy = null): bool
    {
        if ($this->isRejected()) {
            return false;
        }

        $this->update([
            'status' => ApprovalStatus::Rejected,
            'approved_at' => now(),
            'approved_by' => $rejectedBy ?? auth()->id(),
            'rejection_reason' => $reason,
        ]);

        return true;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = self::withoutGlobalScopes()
            ->where('status', ApprovalStatus::Pending)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public function initializeHasApprovalStatus(): void
    {
        $this->fillable = array_merge($this->fillable, [
            'status',
            'approved_at',
            'approved_by',
            'rejection_reason',
        ]);

        $this->casts = array_merge($this->casts ?? [], [
            'status' => ApprovalStatus::class,
            'approved_at' => 'datetime',
        ]);
    }
}
