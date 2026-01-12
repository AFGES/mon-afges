<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Models\Scopes\ApprovedScope;
use App\Models\Scopes\CurrentlyScope;
use App\Traits\HasApprovalStatus;
use App\Traits\HasPeriod;
use Illuminate\Auth\MustVerifyEmail as AuthMustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ScopedBy([CurrentlyScope::class, ApprovedScope::class])]
final class Delegate extends Model implements MustVerifyEmail
{
    use AuthMustVerifyEmail, HasApprovalStatus, HasPeriod, SoftDeletes;

    /** @use HasFactory<\Database\Factories\DelegateFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        self::creating(function (Delegate $delegate) {
            if (! $delegate->email && $delegate->user) {
                $delegate->email = $delegate->user->email;
            }

            // Mark delegate email as verified if user's email is verified and matches
            if ($delegate->user &&
                $delegate->user->hasVerifiedEmail() &&
                $delegate->email === $delegate->user->email &&
                is_null($delegate->email_verified_at)) {
                $delegate->email_verified_at = now();
            }
        });
    }

    public $fillable = [
        'user_id',
        'organization_id',
        'seat_id',
        'email',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class);
    }

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
}
