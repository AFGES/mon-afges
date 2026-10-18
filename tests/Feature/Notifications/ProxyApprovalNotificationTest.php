<?php

declare(strict_types=1);

use App\Models\Delegate;
use App\Models\Proxy;
use App\Models\Seat;
use App\Models\User;
use App\Notifications\ProxyApprovalNotification;
use App\Notifications\ProxyApprovedNotification;

it('does not turn a Markdown link in a seat label into a link', function (string $notificationClass) {
    $seat = Seat::factory()->create();
    $delegate = Delegate::factory()->approved()->create([
        'seat_id' => $seat->id,
        'user_id' => User::factory()->create(['first_name' => '[x](https://evil.example)', 'last_name' => 'Doe']),
    ]);
    $proxy = Proxy::factory()->create(['delegator_seat_id' => $seat->id, 'proxy_holder_seat_id' => $seat->id]);

    expect($seat->label())->toContain('evil.example');

    $content = (string) (new $notificationClass($proxy))->toMail(User::factory()->make())->render();

    expect($content)->not->toContain('<a href="https://evil.example"');
})->with([ProxyApprovalNotification::class, ProxyApprovedNotification::class]);
