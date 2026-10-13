<?php

declare(strict_types=1);

use App\Filament\Clusters\DemocraticTimes\Resources\Delegates\DelegateResource;
use App\Filament\Resources\Volunteers\VolunteerResource;
use App\Models\Delegate;
use App\Models\User;
use App\Models\Volunteer;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'Update:Delegate', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'Update:Volunteer', 'guard_name' => 'web']);
});

test('signed delegate approve url approves and redirects to the edit page', function () {
    $approver = User::factory()->create();
    $approver->givePermissionTo('Update:Delegate');
    $delegate = Delegate::withoutGlobalScopes()->create(Delegate::factory()->make()->toArray());

    actingAs($approver)
        ->get(URL::signedRoute('delegates.approve', ['delegate' => $delegate->id]))
        ->assertRedirect(DelegateResource::getUrl('edit', ['record' => $delegate]));

    expect($delegate->refresh()->isApproved())->toBeTrue();
});

test('signed delegate reject url rejects and redirects to the edit page', function () {
    $approver = User::factory()->create();
    $approver->givePermissionTo('Update:Delegate');
    $delegate = Delegate::withoutGlobalScopes()->create(Delegate::factory()->make()->toArray());

    actingAs($approver)
        ->get(URL::signedRoute('delegates.reject', ['delegate' => $delegate->id]))
        ->assertRedirect(DelegateResource::getUrl('edit', ['record' => $delegate]));

    expect($delegate->refresh()->isRejected())->toBeTrue();
});

test('signed volunteer approve url approves and redirects to the edit page', function () {
    $approver = User::factory()->create();
    $approver->givePermissionTo('Update:Volunteer');
    $volunteer = Volunteer::withoutGlobalScopes()->create(Volunteer::factory()->make()->toArray());

    actingAs($approver)
        ->get(URL::signedRoute('volunteers.approve', ['volunteer' => $volunteer->id]))
        ->assertRedirect(VolunteerResource::getUrl('edit', ['record' => $volunteer]));

    expect($volunteer->refresh()->isApproved())->toBeTrue();
});

test('signed volunteer reject url rejects and redirects to the edit page', function () {
    $approver = User::factory()->create();
    $approver->givePermissionTo('Update:Volunteer');
    $volunteer = Volunteer::withoutGlobalScopes()->create(Volunteer::factory()->make()->toArray());

    actingAs($approver)
        ->get(URL::signedRoute('volunteers.reject', ['volunteer' => $volunteer->id]))
        ->assertRedirect(VolunteerResource::getUrl('edit', ['record' => $volunteer]));

    expect($volunteer->refresh()->isRejected())->toBeTrue();
});

test('unsigned approval urls are rejected', function () {
    $approver = User::factory()->create();
    $approver->givePermissionTo('Update:Delegate');
    $delegate = Delegate::withoutGlobalScopes()->create(Delegate::factory()->make()->toArray());

    actingAs($approver)
        ->get(route('delegates.approve', ['delegate' => $delegate->id]))
        ->assertForbidden();

    expect($delegate->refresh()->isPending())->toBeTrue();
});

test('users without update permission cannot approve through a signed url', function () {
    $user = User::factory()->create();
    $delegate = Delegate::withoutGlobalScopes()->create(Delegate::factory()->make()->toArray());

    actingAs($user)
        ->get(URL::signedRoute('delegates.approve', ['delegate' => $delegate->id]))
        ->assertForbidden();

    expect($delegate->refresh()->isPending())->toBeTrue();
});
