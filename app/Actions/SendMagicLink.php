<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Notifications\MagicLinkNotification;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

final class SendMagicLink
{
    private const int MAX_ATTEMPTS_PER_EMAIL = 5;

    private const int MAX_ATTEMPTS_PER_IP = 30;

    private const int DECAY_SECONDS = 3600;

    private const int TOKEN_TTL_MINUTES = 15;

    /**
     * Send a magic link to the given email when an account exists.
     *
     * Behaves identically for unknown emails so accounts cannot be enumerated.
     *
     * @throws TooManyRequestsException When the per-email or per-IP limit is exceeded.
     */
    public function handle(string $email, ?string $ipAddress): void
    {
        $email = Str::trim($email);

        $this->hitRateLimiters(Str::lower($email), $ipAddress);

        $user = User::where('email', $email)->first();

        if (! $user) {
            return;
        }

        $token = Str::random(64);

        $user->update([
            'magic_link_token' => Hash::make($token),
            'magic_link_token_expires_at' => now()->addMinutes(self::TOKEN_TTL_MINUTES),
        ]);

        $user->notify(new MagicLinkNotification($token));
    }

    /**
     * @throws TooManyRequestsException
     */
    private function hitRateLimiters(string $email, ?string $ipAddress): void
    {
        $limits = [
            "magic-link:email:{$email}" => self::MAX_ATTEMPTS_PER_EMAIL,
            "magic-link:ip:{$ipAddress}" => self::MAX_ATTEMPTS_PER_IP,
        ];

        foreach ($limits as $key => $maxAttempts) {
            if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                throw new TooManyRequestsException(self::class, __FUNCTION__, (string) $ipAddress, RateLimiter::availableIn($key));
            }
        }

        foreach (array_keys($limits) as $key) {
            RateLimiter::hit($key, self::DECAY_SECONDS);
        }
    }
}
