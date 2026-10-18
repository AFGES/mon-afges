<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Contracts\Approvable;
use App\Models\Scopes\ApprovedScope;
use App\Traits\HasApprovalStatus;
use Database\Factories\ProxyFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ScopedBy([ApprovedScope::class])]
/**
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $ended_at
 */
final class Proxy extends Model implements Approvable
{
    use HasApprovalStatus;

    /** @use HasFactory<ProxyFactory> */
    use HasFactory;

    use HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'delegator_seat_id',
        'proxy_holder_seat_id',
        'democratic_time_session_id',
        'started_at',
        'ended_at',
    ];

    protected $table = 'proxies';

    /**
     * The seat whose holder is giving their proxy (cannot be present).
     *
     * @return BelongsTo<Seat, $this>
     */
    public function delegatorSeat(): BelongsTo
    {
        return $this->belongsTo(Seat::class, 'delegator_seat_id');
    }

    /**
     * The seat whose holder is receiving the proxy (will vote on behalf of delegator seat holder).
     *
     * @return BelongsTo<Seat, $this>
     */
    public function proxyHolderSeat(): BelongsTo
    {
        return $this->belongsTo(Seat::class, 'proxy_holder_seat_id');
    }

    /** @return BelongsTo<DemocraticTimeSession, $this> */
    public function democraticTimeSession(): BelongsTo
    {
        return $this->belongsTo(DemocraticTimeSession::class);
    }

    /**
     * Check if the proxy is active at a given time.
     */
    public function isActiveAt(?DateTimeInterface $time = null): bool
    {
        $time = $time ?? now();

        $afterStart = is_null($this->started_at) || $time >= $this->started_at;
        $beforeEnd = is_null($this->ended_at) || $time <= $this->ended_at;

        return $afterStart && $beforeEnd;
    }

    /**
     * Check if both seats are from the same college.
     * Delegates the comparison logic to the Seat model.
     */
    public function areSeatsFromSameCollege(): bool
    {
        if (! $this->delegatorSeat) {
            return false;
        }

        if (! $this->proxyHolderSeat) {
            return false;
        }

        return $this->delegatorSeat->isSameCollegeAs($this->proxyHolderSeat);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }
}
