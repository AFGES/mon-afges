<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SessionSignInFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SessionSignIn extends Model
{
    /** @use HasFactory<SessionSignInFactory> */
    use HasFactory;

    use HasUuids;

    /** @var list<string> */
    protected $fillable = [
        'democratic_time_session_id',
        'seat_id',
        'delegate_id',
        'signed_in_by_seat_id',
        'signed_in_at',
        'signed_out_at',
    ];

    /** @return BelongsTo<DemocraticTimeSession, $this> */
    public function democraticTimeSession(): BelongsTo
    {
        return $this->belongsTo(DemocraticTimeSession::class);
    }

    /** @return BelongsTo<Seat, $this> */
    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class);
    }

    /** @return BelongsTo<Delegate, $this> */
    public function delegate(): BelongsTo
    {
        return $this->belongsTo(Delegate::class);
    }

    /**
     * The seat that signed in on behalf (via proxy).
     *
     * @return BelongsTo<Seat, $this>
     */
    public function signedInBySeat(): BelongsTo
    {
        return $this->belongsTo(Seat::class, 'signed_in_by_seat_id');
    }

    /**
     * Check if the delegate is currently signed in (has not signed out).
     */
    public function isSignedIn(): bool
    {
        return $this->signed_out_at === null;
    }

    /**
     * Check if the delegate signed out early (before session ended).
     */
    public function signedOutEarly(): bool
    {
        if ($this->signed_out_at === null) {
            return false;
        }

        $session = $this->democraticTimeSession;

        if ($session->ended_at === null) {
            return true; // Session hasn't ended yet
        }

        return $this->signed_out_at->isBefore($session->ended_at);
    }

    /**
     * Check if the sign-in was done via proxy.
     */
    public function isProxySignIn(): bool
    {
        return $this->signed_in_by_seat_id !== null && $this->signed_in_by_seat_id !== $this->seat_id;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'signed_in_at' => 'datetime',
            'signed_out_at' => 'datetime',
        ];
    }
}
