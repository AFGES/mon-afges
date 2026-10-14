<?php

declare(strict_types=1);

use App\Actions\SendMagicLink;
use App\Models\User;
use App\Notifications\MagicLinkNotification;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Illuminate\Support\Facades\Notification;

it('shares the per-email limit between the route and the login page', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'test@afges.org']);

    foreach (range(1, 5) as $attempt) {
        $this->withServerVariables(['REMOTE_ADDR' => "10.0.0.{$attempt}"])
            ->post(route('magicLink.request'), ['email' => 'test@afges.org'])
            ->assertRedirect();
    }

    expect(fn () => app(SendMagicLink::class)->handle('TEST@afges.org', '10.0.0.50'))
        ->toThrow(TooManyRequestsException::class);
    Notification::assertSentToTimes($user, MagicLinkNotification::class, 5);
});

it('shares the per-ip limit across emails', function () {
    Notification::fake();

    foreach (range(1, 30) as $attempt) {
        app(SendMagicLink::class)->handle("user{$attempt}@afges.org", '10.0.0.1');
    }

    expect(fn () => app(SendMagicLink::class)->handle('user31@afges.org', '10.0.0.1'))
        ->toThrow(TooManyRequestsException::class);
});
