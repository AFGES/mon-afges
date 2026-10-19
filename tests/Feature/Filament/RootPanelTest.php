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

it('lets a verified user with another email domain reach the panel', function () {
    $user = User::factory()->create(['email' => 'member@example.org']);

    actingAs($user)
        ->get(Filament::getPanel('root')->getUrl())
        ->assertOk();

    expect($user->canAccessPanel(Filament::getPanel('root')))->toBeTrue();
});

it('redirects an unverified user to the email verification prompt', function () {
    $user = User::factory()->unverified()->create();

    actingAs($user)
        ->get(Filament::getPanel('root')->getUrl())
        ->assertRedirect(Filament::getPanel('root')->getEmailVerificationPromptUrl());
});
