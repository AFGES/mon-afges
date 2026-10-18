<?php

declare(strict_types=1);

namespace App\Traits;

use App\Enums\ApprovalStatus;
use App\Models\Scopes\ApprovedScope;
use App\Models\Scopes\CurrentlyScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasApprovalStatus
{
    /**
     * Count pending records regardless of the approval and period scopes (soft deletes stay applied).
     */
    public static function pendingCount(): int
    {
        return self::withoutGlobalScopes([ApprovedScope::class, CurrentlyScope::class])
            ->pending()
            ->count();
    }

    /**
     * Scope a query to only include pending records.
     *
     * @param  Builder<static>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->where('approval_status', ApprovalStatus::Pending);
    }

    /**
     * Scope a query to only include approved records.
     *
     * @param  Builder<static>  $query
     */
    public function scopeApproved(Builder $query): void
    {
        $query->where('approval_status', ApprovalStatus::Approved);
    }

    /**
     * Scope a query to only include rejected records.
     *
     * @param  Builder<static>  $query
     */
    public function scopeRejected(Builder $query): void
    {
        $query->where('approval_status', ApprovalStatus::Rejected);
    }

    /**
     * Scope a query to only include records with a specific approval status.
     *
     * @param  Builder<static>  $query
     */
    public function scopeWithApprovalStatus(Builder $query, ApprovalStatus $status): void
    {
        $query->where('approval_status', $status);
    }

    /**
     * Scope a query to only include records approved by a specific user.
     *
     * @param  Builder<static>  $query
     */
    public function scopeApprovedBy(Builder $query, int|string $userId): void
    {
        $query->where('approved_by', $userId)
            ->where('approval_status', ApprovalStatus::Approved);
    }

    /**
     * Scope a query to only include records rejected by a specific user.
     *
     * @param  Builder<static>  $query
     */
    public function scopeRejectedBy(Builder $query, int|string $userId): void
    {
        $query->where('approved_by', $userId)
            ->where('approval_status', ApprovalStatus::Rejected);
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isPending(): bool
    {
        return $this->approval_status === ApprovalStatus::Pending;
    }

    public function isApproved(): bool
    {
        return $this->approval_status === ApprovalStatus::Approved;
    }

    public function isRejected(): bool
    {
        return $this->approval_status === ApprovalStatus::Rejected;
    }

    public function approve(int|string|null $approvedBy = null): bool
    {
        if ($this->isApproved()) {
            return false;
        }

        $this->update([
            'approval_status' => ApprovalStatus::Approved,
            'approved_at' => now(),
            'approved_by' => $approvedBy ?? auth()->id(),
            'rejection_reason' => null,
        ]);

        return true;
    }

    public function reject(?string $reason = null, int|string|null $rejectedBy = null): bool
    {
        if ($this->isRejected()) {
            return false;
        }

        $this->update([
            'approval_status' => ApprovalStatus::Rejected,
            'approved_at' => now(),
            'approved_by' => $rejectedBy ?? auth()->id(),
            'rejection_reason' => $reason,
        ]);

        return true;
    }

    public function initializeHasApprovalStatus(): void
    {
        $this->fillable = array_merge($this->fillable, [
            'approval_status',
            'approved_at',
            'approved_by',
            'rejection_reason',
        ]);

        $this->mergeCasts(array_diff_key([
            'approval_status' => ApprovalStatus::class,
            'approved_at' => 'datetime',
        ], $this->getCasts()));
    }
}
