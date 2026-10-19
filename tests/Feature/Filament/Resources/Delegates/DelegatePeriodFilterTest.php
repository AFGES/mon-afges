<?php

declare(strict_types=1);

use App\Filament\Clusters\DemocraticTimes\Resources\Delegates\Pages\ListDelegates;
use App\Filament\Exports\DelegateExporter;
use App\Models\Delegate;
use App\Models\User;
use Filament\Actions\ExportAction;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('ViewAny:Delegate', 'web'));
    $user->givePermissionTo(Permission::findOrCreate('Create:Delegate', 'web'));
    actingAs($user);

    $this->current = Delegate::factory()->approved()->create(['started_at' => now()->subMonth(), 'ended_at' => null]);
    $this->old = Delegate::factory()->approved()->create(['started_at' => now()->subYear(), 'ended_at' => now()->subMonth()]);
    $this->future = Delegate::factory()->approved()->create(['started_at' => now()->addMonth(), 'ended_at' => null]);
});

it('lists only current delegates by default', function () {
    livewire(ListDelegates::class)
        ->assertCanSeeTableRecords([$this->current])
        ->assertCanNotSeeTableRecords([$this->old, $this->future]);
});

it('lists ended delegates with the old period filter', function () {
    livewire(ListDelegates::class)
        ->filterTable('period', ['old'])
        ->assertCanSeeTableRecords([$this->old])
        ->assertCanNotSeeTableRecords([$this->current, $this->future]);
});

it('lists upcoming delegates with the future period filter', function () {
    livewire(ListDelegates::class)
        ->filterTable('period', ['future'])
        ->assertCanSeeTableRecords([$this->future])
        ->assertCanNotSeeTableRecords([$this->current, $this->old]);
});

it('lists every period when all periods are selected', function () {
    livewire(ListDelegates::class)
        ->filterTable('period', ['current', 'old', 'future'])
        ->assertCanSeeTableRecords([$this->current, $this->old, $this->future]);
});

it('exports the delegates of the selected period', function () {
    $exports = ExportAction::fake();

    livewire(ListDelegates::class)
        ->filterTable('period', ['old'])
        ->callAction('export');

    $exports->assertDispatched(
        DelegateExporter::class,
        fn ($export, Builder $query): bool => $query->pluck('id')->all() === [$this->old->id],
    );
});
