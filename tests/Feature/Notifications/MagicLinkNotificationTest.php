<?php

declare(strict_types=1);

use App\Enums\MailTemplateKey;
use App\Models\User;
use App\Notifications\MagicLinkNotification;
use FinityLabs\FinMail\Models\SentEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    app()->setLocale('fr');
});

it('is sent by mail and queued', function () {
    $notification = new MagicLinkNotification('token');

    expect($notification->via(new User))->toBe(['mail'])
        ->and($notification)->toBeInstanceOf(ShouldQueue::class);
});

it('renders the magic link template for the user', function () {
    $user = User::factory()->make(['email' => 'test@afges.org']);

    $mail = (new MagicLinkNotification('secret-token'))->toMail($user);

    expect($mail->getTemplate()->key)->toBe(MailTemplateKey::MagicLink->value)
        ->and($mail->hasTo('test@afges.org'))->toBeTrue()
        ->and($mail->envelope()->subject)->toBe('Votre lien de connexion');
});

it('links to a signed verification url carrying the email and token', function () {
    $user = User::factory()->make(['email' => 'test@afges.org']);

    $links = templateMailLinks((new MagicLinkNotification('secret-token'))->toMail($user));

    parse_str((string) parse_url($links[0], PHP_URL_QUERY), $query);
    expect($links[0])->toStartWith(route('magicLink.verify'))
        ->and($query['email'])->toBe('test@afges.org')
        ->and($query['token'])->toBe('secret-token')
        ->and($query)->toHaveKeys(['expires', 'signature']);
});

it('expires the link after the given number of minutes', function () {
    $this->freezeTime();
    $user = User::factory()->make(['email' => 'test@afges.org']);

    $mail = (new MagicLinkNotification('secret-token', expiresInMinutes: 30))->toMail($user);

    parse_str((string) parse_url(templateMailLinks($mail)[0], PHP_URL_QUERY), $query);
    expect((int) $query['expires'])->toBe(now()->addMinutes(30)->getTimestamp())
        ->and($mail->render())->toContain('30 minutes');
});

it('sends the mail from the queued notification job without queueing it again', function () {
    Queue::fake();
    $user = User::factory()->create();

    $user->notify(new MagicLinkNotification('secret-token'));

    Queue::assertPushed(SendQueuedNotifications::class, function (SendQueuedNotifications $job): bool {
        $job->handle(app(ChannelManager::class));

        return true;
    });
    Queue::assertNotPushed(SendQueuedMailable::class);
    expect(Mail::mailer()->getSymfonyTransport()->messages())->toHaveCount(1);
});

it('does not store the rendered body, which holds the login link, in the sent-emails log', function () {
    $user = User::factory()->create();

    $user->notify(new MagicLinkNotification('secret-token'));

    expect(SentEmail::query()->sole())
        ->rendered_body->toBeNull()
        ->to->toBe([$user->email]);
});
