<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Models\Contracts\Approvable;
use App\Models\Scopes\ApprovedScope;
use App\Models\Scopes\CurrentlyScope;
use App\Traits\HasApprovalStatus;
use App\Traits\HasPeriod;
use Database\Factories\DelegateFactory;
use Illuminate\Auth\MustVerifyEmail as AuthMustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
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
final class Delegate extends Model implements Approvable, MustVerifyEmail
{
    use AuthMustVerifyEmail;
    use HasApprovalStatus;

    /** @use HasFactory<DelegateFactory> */
    use HasFactory;

    use HasPeriod;
    use HasUuids;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'organization_id',
        'seat_id',
        'email',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<Seat, $this> */
    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class);
    }

    protected static function booted(): void
    {
        self::creating(function (Delegate $delegate): void {
            if (! $delegate->email && $delegate->user) {
                $delegate->email = $delegate->user->email;
            }

            if (self::shouldAutoVerifyEmail($delegate)) {
                $delegate->email_verified_at = now();
            }
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'started_at' => 'date',
            'ended_at' => 'date',
            'approved_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'approval_status' => ApprovalStatus::class,
        ];
    }

    /**
     * A delegate email is auto-verified when it matches its user's already verified email.
     */
    private static function shouldAutoVerifyEmail(self $delegate): bool
    {
        if (! $delegate->user) {
            return false;
        }

        if (! $delegate->user->hasVerifiedEmail()) {
            return false;
        }

        if ($delegate->email !== $delegate->user->email) {
            return false;
        }

        return is_null($delegate->email_verified_at);
    }
}
