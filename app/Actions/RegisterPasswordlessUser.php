<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Illuminate\Support\Str;
use Illuminate\Support\Timebox;

final class RegisterPasswordlessUser
{
    private const int MINIMUM_RESPONSE_MICROSECONDS = 500_000;

    public function __construct(
        private readonly SendMagicLink $sendMagicLink,
        private readonly Timebox $timebox,
    ) {}

    /**
     * Create an unverified account without password when the email is unknown, then send a magic link.
     *
     * A known email creates nothing and still receives a link, so the response does not reveal
     * whether the account exists. The whole call runs inside a Timebox so the insert does not
     * show in the response time. Until the address is verified, the latest names submitted
     * replace the stored ones, so a squatter cannot impose names on the real owner.
     *
     * @param  array{first_name: string, last_name: string, email: string}  $data
     *
     * @throws TooManyRequestsException When the magic link rate limit is exceeded.
     */
    public function handle(array $data, ?string $ipAddress): void
    {
        $this->timebox->call(function () use ($data, $ipAddress): void {
            $names = [
                'first_name' => Str::trim($data['first_name']),
                'last_name' => Str::trim($data['last_name']),
            ];

            $user = User::query()->firstOrCreate(['email' => Str::lower(Str::trim($data['email']))], $names);

            if (! $user->wasRecentlyCreated && ! $user->hasVerifiedEmail()) {
                $user->update($names);
            }

            $this->sendMagicLink->handle($user->email, $ipAddress);
        }, self::MINIMUM_RESPONSE_MICROSECONDS);
    }
}
