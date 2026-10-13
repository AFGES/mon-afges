<?php

declare(strict_types=1);

namespace App\Traits;

use App\Enums\ApprovalStatus;
use App\Models\User;
use App\Notifications\ApprovalApprovedNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasApprovalStatus
{
    public static function getNavigationBadge(): ?string
    {
        $count = self::withoutGlobalScopes()
            ->pending()
            ->count();

        return $count > 0 ? (string) $count : null;
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

    public function approve(?string $approvedBy = null): bool
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

        $this->sendApprovalNotification();

        return true;
    }

    public function reject(?string $reason = null, ?string $rejectedBy = null): bool
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

    /**
     * Send a notification to the user when their request is approved.
     */
    protected function sendApprovalNotification(): void
    {
        $notifiable = $this->getApprovalNotifiable();

        if ($notifiable !== null) {
            $notifiable->notify(new ApprovalApprovedNotification($this));
        }
    }

    /**
     * Get the notifiable user for approval notifications.
     * Override this method if the model has a different relationship name.
     */
    protected function getApprovalNotifiable(): ?object
    {
        if (method_exists($this, 'user') && $this->user !== null) {
            return $this->user;
        }

        return null;
    }
}
