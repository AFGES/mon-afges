<?php

declare(strict_types=1);

use App\Filament\Clusters\DemocraticTimes\Resources\Delegates\DelegateResource;
use App\Models\Delegate;
use App\Models\User;
use App\Notifications\ApprovalApprovedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    app()->setLocale('en');
    Permission::create(['name' => 'Update:Delegate', 'guard_name' => 'web']);
});

it('rejects the delegate through a signed url, flashes the result and redirects to the edit page', function () {
    $delegate = Delegate::factory()->create();
    $approver = User::factory()->create()->givePermissionTo('Update:Delegate');
    Notification::fake([ApprovalApprovedNotification::class]);

    $response = $this->actingAs($approver)->get(URL::signedRoute('delegates.reject', ['delegate' => $delegate->id]));

    $response->assertRedirect(DelegateResource::getUrl('edit', ['record' => $delegate]))
        ->assertSessionHas('success', 'Delegate rejected successfully');
    expect($delegate->refresh()->isRejected())->toBeTrue()
        ->and($delegate->approved_by)->toBe($approver->id);
    Notification::assertNotSentTo($delegate->user, ApprovalApprovedNotification::class);
});

it('returns 403 when the url is not signed', function () {
    $delegate = Delegate::factory()->create();
    $approver = User::factory()->create()->givePermissionTo('Update:Delegate');

    $response = $this->actingAs($approver)->get(route('delegates.reject', ['delegate' => $delegate->id]));

    $response->assertForbidden();
    expect($delegate->refresh()->isPending())->toBeTrue();
});

it('returns 403 when the signed url targets another delegate', function () {
    $delegate = Delegate::factory()->create();
    $other = Delegate::factory()->create();
    $approver = User::factory()->create()->givePermissionTo('Update:Delegate');
    $url = str_replace($delegate->id, $other->id, URL::signedRoute('delegates.reject', ['delegate' => $delegate->id]));

    $response = $this->actingAs($approver)->get($url);

    $response->assertForbidden();
    expect($other->refresh()->isPending())->toBeTrue();
});

it('returns 403 for a user without the update permission', function () {
    $delegate = Delegate::factory()->create();
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(URL::signedRoute('delegates.reject', ['delegate' => $delegate->id]));

    $response->assertForbidden();
    expect($delegate->refresh()->isPending())->toBeTrue();
});

it('returns 404 for an unknown delegate', function () {
    $approver = User::factory()->create()->givePermissionTo('Update:Delegate');

    $response = $this->actingAs($approver)->get(URL::signedRoute('delegates.reject', ['delegate' => (string) Str::uuid()]));

    $response->assertNotFound();
});

it('redirects a guest opening a valid signed url to the login page and leaves the delegate pending', function () {
    $delegate = Delegate::factory()->create();

    $response = $this->get(URL::signedRoute('delegates.reject', ['delegate' => $delegate->id]));

    $response->assertRedirect(route('filament.root.auth.login'));
    expect($delegate->refresh()->isPending())->toBeTrue();
});
