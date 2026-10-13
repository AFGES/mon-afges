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

it('approves the delegate through a signed url, flashes the result and redirects to the edit page', function () {
    $delegate = Delegate::factory()->create();
    $approver = User::factory()->create()->givePermissionTo('Update:Delegate');
    Notification::fake([ApprovalApprovedNotification::class]);

    $response = $this->actingAs($approver)->get(URL::signedRoute('delegates.approve', ['delegate' => $delegate->id]));

    $response->assertRedirect(DelegateResource::getUrl('edit', ['record' => $delegate]))
        ->assertSessionHas('success', 'Delegate approved successfully');
    expect($delegate->refresh()->isApproved())->toBeTrue()
        ->and($delegate->approved_by)->toBe($approver->id);
    Notification::assertSentTo(
        $delegate->user,
        ApprovalApprovedNotification::class,
        fn (ApprovalApprovedNotification $notification): bool => $notification->approvable->is($delegate),
    );
});

it('returns 403 when the url is not signed', function () {
    $delegate = Delegate::factory()->create();
    $approver = User::factory()->create()->givePermissionTo('Update:Delegate');

    $response = $this->actingAs($approver)->get(route('delegates.approve', ['delegate' => $delegate->id]));

    $response->assertForbidden();
    expect($delegate->refresh()->isPending())->toBeTrue();
});

it('returns 403 when the signed url targets another delegate', function () {
    $delegate = Delegate::factory()->create();
    $other = Delegate::factory()->create();
    $approver = User::factory()->create()->givePermissionTo('Update:Delegate');
    $url = str_replace($delegate->id, $other->id, URL::signedRoute('delegates.approve', ['delegate' => $delegate->id]));

    $response = $this->actingAs($approver)->get($url);

    $response->assertForbidden();
    expect($other->refresh()->isPending())->toBeTrue();
});

it('returns 403 for a user without the update permission', function () {
    $delegate = Delegate::factory()->create();
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(URL::signedRoute('delegates.approve', ['delegate' => $delegate->id]));

    $response->assertForbidden();
    expect($delegate->refresh()->isPending())->toBeTrue();
});

it('returns 404 for an unknown delegate', function () {
    $approver = User::factory()->create()->givePermissionTo('Update:Delegate');

    $response = $this->actingAs($approver)->get(URL::signedRoute('delegates.approve', ['delegate' => (string) Str::uuid()]));

    $response->assertNotFound();
});
