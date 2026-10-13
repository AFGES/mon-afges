<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\MagicLinkNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

it('is sent by mail and queued', function () {
    $notification = new MagicLinkNotification('token');

    expect($notification->via(new User))->toBe(['mail'])
        ->and($notification)->toBeInstanceOf(ShouldQueue::class);
});

it('builds a signed verification url carrying the email and token', function () {
    $user = User::factory()->make(['email' => 'test@afges.org']);

    $mail = (new MagicLinkNotification('secret-token'))->toMail($user);

    parse_str((string) parse_url($mail->actionUrl, PHP_URL_QUERY), $query);
    expect($mail->subject)->toBe('Votre lien de connexion')
        ->and($mail->actionUrl)->toStartWith(route('magicLink.verify'))
        ->and($query['email'])->toBe('test@afges.org')
        ->and($query['token'])->toBe('secret-token')
        ->and($query)->toHaveKeys(['expires', 'signature']);
});

it('expires the link after the given number of minutes', function () {
    $this->freezeTime();
    $user = User::factory()->make(['email' => 'test@afges.org']);

    $mail = (new MagicLinkNotification('secret-token', expiresInMinutes: 30))->toMail($user);

    parse_str((string) parse_url($mail->actionUrl, PHP_URL_QUERY), $query);
    expect((int) $query['expires'])->toBe(now()->addMinutes(30)->getTimestamp())
        ->and(collect($mail->outroLines)->implode(' '))->toContain('30 minutes');
});
