<?php

declare(strict_types=1);

use App\Enums\ApprovalStatus;
use App\Filament\Actions\ApproveAction;
use App\Filament\Actions\BulkApproveAction;
use App\Models\Delegate;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    actingAs(User::factory()->create()->assignRole('super_admin'));
});

it('hides the generic approve action for delegates', function () {
    $delegate = Delegate::factory()->pending()->create();

    expect(ApproveAction::make()->record($delegate)->isVisible())->toBeFalse();
});

it('refuses to approve a delegate through the generic approve action', function () {
    $delegate = Delegate::factory()->pending()->create();

    $action = ApproveAction::make()->record($delegate);

    expect(fn () => $action->call())->toThrow(LogicException::class);
    expect($delegate->fresh()->approval_status)->toBe(ApprovalStatus::Pending);
});

it('refuses to bulk approve delegates through the generic bulk action', function () {
    $delegates = Delegate::factory()->pending()->count(2)->create();

    $action = BulkApproveAction::make();

    expect(fn () => $action->evaluate($action->getActionFunction(), ['selectedRecords' => new Collection($delegates->all()), 'data' => []]))->toThrow(LogicException::class);
    expect(Delegate::withoutGlobalScopes()->pending()->count())->toBe(2);
});
