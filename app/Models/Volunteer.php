<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Models\Contracts\Approvable;
use App\Models\Scopes\ApprovedScope;
use App\Models\Scopes\CurrentlyScope;
use App\Traits\HasApprovalStatus;
use App\Traits\HasPeriod;
use Database\Factories\VolunteerFactory;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property \Illuminate\Support\Carbon $started_at
 * @property \Illuminate\Support\Carbon|null $ended_at
 */
#[ScopedBy([CurrentlyScope::class, ApprovedScope::class])]
final class Volunteer extends Model implements Approvable
{
    use HasApprovalStatus;

    /** @use HasFactory<VolunteerFactory> */
    use HasFactory;

    use HasPeriod;
    use HasUuids;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'position_title',
        'user_id',
        'organization_id',
    ];

    /**
     * Scope a query to volunteers of an existing organization still waiting for
     * a position title, as left by the email-domain auto-link.
     *
     * @param  Builder<static>  $query
     */
    public function scopeAwaitingPositionTitle(Builder $query): void
    {
        $query->whereNull('position_title')->whereHas('organization');
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'approved_at' => 'datetime',
            'approval_status' => ApprovalStatus::class,
        ];
    }
}
