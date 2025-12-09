<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Email extends Model
{
    /** @use HasFactory<\Database\Factories\EmailFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        self::updated(function (Email $email) {
            if ($email->wasChanged('verified_at') && $email->isVerified()) {
                app(\App\Services\EmailVerificationVolunteerService::class)
                    ->handleVerifiedEmail($email);
            }
        });
    }

    protected $fillable = [
        'user_id',
        'email',
        'is_primary',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPrimary(): bool
    {
        return $this->is_primary;
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function markAsVerified(): void
    {
        $this->verified_at = now();
        $this->save();
    }

    public function markAsPrimary(): void
    {
        // Remove primary flag from other emails
        $this->user->emails()->where('id', '!=', $this->id)->update(['is_primary' => false]);

        $this->is_primary = true;
        $this->save();
    }
}
