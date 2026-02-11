<?php

declare(strict_types=1);

use App\Filament\Clusters\DemocraticTimes\Resources\Sessions\Widgets\SessionQuorumStatusWidget;
use App\Models\DemocraticTimeSession;
use App\Models\User;
use Spatie\Permission\Models\Role;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin']);
    $this->actingAsUser = User::factory()->create();
    $this->actingAsUser->assignRole('super_admin');
    $this->actingAs($this->actingAsUser);
});

it('can render the widget', function () {
    $session = DemocraticTimeSession::factory()->create();

    livewire(SessionQuorumStatusWidget::class, [
        'record' => $session,
    ])
        ->assertOk();
});

it('displays a heading', function () {
    $session = DemocraticTimeSession::factory()->create();

    $widget = livewire(SessionQuorumStatusWidget::class, [
        'record' => $session,
    ]);

    $heading = $widget->instance()->getHeading();

    expect($heading)->not->toBeNull()
        ->and($heading)->toBeString();
});

it('displays quorum status in heading', function () {
    $session = DemocraticTimeSession::factory()->create();

    $widget = livewire(SessionQuorumStatusWidget::class, [
        'record' => $session,
    ]);

    $heading = $widget->instance()->getHeading();

    // Heading should contain either "met" or "not_met" translation
    $containsMet = str_contains($heading, __('emargement.quorum.met'));
    $containsNotMet = str_contains($heading, __('emargement.quorum.not_met'));

    expect($containsMet || $containsNotMet)->toBeTrue();
});

it('handles null record gracefully', function () {
    $widget = livewire(SessionQuorumStatusWidget::class);

    $heading = $widget->instance()->getHeading();

    expect($heading)->toBeNull();
});
