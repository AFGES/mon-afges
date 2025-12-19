<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Models\Scopes\ApprovedScope;
use App\Models\Scopes\CurrentlyScope;
use App\Traits\HasApprovalStatus;
use App\Traits\HasPeriod;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ScopedBy([CurrentlyScope::class, ApprovedScope::class])]
final class Delegate extends Model
{
    use HasApprovalStatus;

    /** @use HasFactory<\Database\Factories\DelegateFactory> */
    use HasFactory;

    use HasPeriod;
    use SoftDeletes;

    public $fillable = [
        'user_id',
        'organization_id',
        'seat_id',
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
            'status' => ApprovalStatus::class,
        ];
    }
}
