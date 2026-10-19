<?php

declare(strict_types=1);

use App\Enums\MailTemplateKey;
use App\Models\Delegate;
use App\Models\Proxy;
use App\Models\Seat;
use App\Models\User;
use App\Notifications\ProxyApprovalNotification;
use App\Notifications\ProxyApprovedNotification;

beforeEach(function () {
    app()->setLocale('fr');
});

it('renders its template with the proxy seats and session', function (string $notificationClass, MailTemplateKey $key) {
    $proxy = Proxy::factory()->create();
    $recipient = User::factory()->make(['email' => 'recipient@afges.org']);

    $mail = (new $notificationClass($proxy))->toMail($recipient);

    expect($mail->getTemplate()->key)->toBe($key->value)
        ->and($mail->hasTo('recipient@afges.org'))->toBeTrue()
        ->and($mail->render())
        ->toContain('Délégant-e : '.e($proxy->delegatorSeat->label()))
        ->toContain('Mandataire : '.e($proxy->proxyHolderSeat->label()))
        ->toContain(e($proxy->democraticTimeSession->democraticTimeType->name));
})->with([
    'approval requested' => [ProxyApprovalNotification::class, MailTemplateKey::ProxyApprovalRequested],
    'approved' => [ProxyApprovedNotification::class, MailTemplateKey::ProxyApproved],
]);

it('escapes the seat labels in the rendered mail', function (string $notificationClass) {
    $seat = Seat::factory()->create();
    Delegate::factory()->approved()->create([
        'seat_id' => $seat->id,
        'user_id' => User::factory()->create(['first_name' => '<a href=x>', 'last_name' => 'Doe']),
    ]);
    $proxy = Proxy::factory()->create(['delegator_seat_id' => $seat->id, 'proxy_holder_seat_id' => $seat->id]);

    $content = (new $notificationClass($proxy))->toMail(User::factory()->make())->render();

    expect($content)
        ->toContain('&lt;a href=x&gt;')
        ->not->toContain('<a href=x>');
})->with([ProxyApprovalNotification::class, ProxyApprovedNotification::class]);
