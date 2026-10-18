<?php

declare(strict_types=1);

use App\Actions\SendMagicLink;
use App\Models\User;
use App\Notifications\MagicLinkNotification;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Timebox;

beforeEach(function () {
    $this->withoutDefer();
});

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

it('runs the lookup inside a timebox for known and unknown emails', function (string $email) {
    Notification::fake();
    User::factory()->create(['email' => 'known@afges.org']);
    $timebox = Mockery::mock(Timebox::class);
    $timebox->shouldReceive('call')
        ->once()
        ->withArgs(fn (Closure $callback, int $microseconds): bool => $microseconds === 250_000)
        ->andReturnUsing(fn (Closure $callback) => $callback($timebox));
    app()->instance(Timebox::class, $timebox);

    app(SendMagicLink::class)->handle($email, '10.0.0.1');
})->with(['known' => 'known@afges.org', 'unknown' => 'unknown@afges.org']);

it('stores the token and notifies a known email but sends nothing for an unknown one', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'known@afges.org']);

    app(SendMagicLink::class)->handle('known@afges.org', '10.0.0.1');
    app(SendMagicLink::class)->handle('unknown@afges.org', '10.0.0.1');

    expect($user->refresh()->magic_link_token)->not->toBeNull();
    Notification::assertSentTimes(MagicLinkNotification::class, 1);
    Notification::assertSentTo($user, MagicLinkNotification::class);
});
