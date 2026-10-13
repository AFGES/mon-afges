<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Facades\Filament;

use function Pest\Laravel\actingAs;

test('login page renders through the panel middleware stack', function () {
    $this->get(Filament::getPanel('root')->getLoginUrl())->assertOk();
});

it('lets a user with an afges.org email access the panel', function () {
    $user = User::factory()->create(['email' => 'member@afges.org']);

    expect($user->canAccessPanel(Filament::getPanel('root')))->toBeTrue();
});

it('forbids a user with another email domain from accessing the panel', function () {
    $user = User::factory()->create(['email' => 'member@example.org']);

    actingAs($user)
        ->get(Filament::getPanel('root')->getUrl())
        ->assertForbidden();

    expect($user->canAccessPanel(Filament::getPanel('root')))->toBeFalse();
});
