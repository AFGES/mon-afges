<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VolunteerStatus;
use App\Models\Scopes\ApprovedScope;
use App\Models\Scopes\CurrentlyScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ScopedBy([CurrentlyScope::class, ApprovedScope::class])]
final class Volunteer extends Model
{
    /** @use HasFactory<\Database\Factories\VolunteerFactory> */
    use HasFactory;

    use SoftDeletes;

    public $fillable = [
        'position_title',
        'started_at',
        'ended_at',
        'user_id',
        'organization_id',
        'status',
        'approved_at',
        'approved_by',
        'rejection_reason',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isPending(): bool
    {
        return $this->status === VolunteerStatus::Pending;
    }

    public function isApproved(): bool
    {
        return $this->status === VolunteerStatus::Approved;
    }

    public function isRejected(): bool
    {
        return $this->status === VolunteerStatus::Rejected;
    }

    public function approve(?int $approvedBy = null): void
    {
        $this->update([
            'status' => VolunteerStatus::Approved,
            'approved_at' => now(),
            'approved_by' => $approvedBy ?? auth()->id(),
            'rejection_reason' => null,
        ]);
    }

    public function reject(string $reason, ?int $rejectedBy = null): void
    {
        $this->update([
            'status' => VolunteerStatus::Rejected,
            'approved_at' => now(),
            'approved_by' => $rejectedBy ?? auth()->id(),
            'rejection_reason' => $reason,
        ]);
    }

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'approved_at' => 'datetime',
            'status' => VolunteerStatus::class,
        ];
    }
}
