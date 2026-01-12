<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Proxy extends Model
{
    /** @use HasFactory<\Database\Factories\ProxyFactory> */
    use HasFactory;

    protected $table = 'proxies';

    public $fillable = [
        'delegator_seat_id',
        'proxy_holder_seat_id',
        'democratic_time_session_id',
        'started_at',
        'ended_at',
    ];

    /**
     * The seat whose holder is giving their proxy (cannot be present).
     */
    public function delegatorSeat(): BelongsTo
    {
        return $this->belongsTo(Seat::class, 'delegator_seat_id');
    }

    /**
     * The seat whose holder is receiving the proxy (will vote on behalf of delegator seat holder).
     */
    public function proxyHolderSeat(): BelongsTo
    {
        return $this->belongsTo(Seat::class, 'proxy_holder_seat_id');
    }

    public function democraticTimeSession(): BelongsTo
    {
        return $this->belongsTo(DemocraticTimeSession::class);
    }

    /**
     * Check if the proxy is active at a given time.
     */
    public function isActiveAt(?\DateTimeInterface $time = null): bool
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
        if (! $this->delegatorSeat || ! $this->proxyHolderSeat) {
            return false;
        }

        return $this->delegatorSeat->isSameCollegeAs($this->proxyHolderSeat);
    }

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }
}
