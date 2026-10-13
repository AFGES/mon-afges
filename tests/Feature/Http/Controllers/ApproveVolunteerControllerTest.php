<?php

declare(strict_types=1);

use App\Filament\Resources\Volunteers\VolunteerResource;
use App\Models\User;
use App\Models\Volunteer;
use App\Notifications\ApprovalApprovedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    app()->setLocale('en');
    Permission::create(['name' => 'Update:Volunteer', 'guard_name' => 'web']);
});

it('approves the volunteer through a signed url, flashes the result and redirects to the edit page', function () {
    $volunteer = Volunteer::factory()->create();
    $approver = User::factory()->create()->givePermissionTo('Update:Volunteer');
    Notification::fake([ApprovalApprovedNotification::class]);

    $response = $this->actingAs($approver)->get(URL::signedRoute('volunteers.approve', ['volunteer' => $volunteer->id]));

    $response->assertRedirect(VolunteerResource::getUrl('edit', ['record' => $volunteer]))
        ->assertSessionHas('success', 'Volunteer approved successfully');
    expect($volunteer->refresh()->isApproved())->toBeTrue()
        ->and($volunteer->approved_by)->toBe($approver->id);
    Notification::assertSentTo(
        $volunteer->user,
        ApprovalApprovedNotification::class,
        fn (ApprovalApprovedNotification $notification): bool => $notification->approvable->is($volunteer),
    );
});

it('returns 403 when the url is not signed', function () {
    $volunteer = Volunteer::factory()->create();
    $approver = User::factory()->create()->givePermissionTo('Update:Volunteer');

    $response = $this->actingAs($approver)->get(route('volunteers.approve', ['volunteer' => $volunteer->id]));

    $response->assertForbidden();
    expect($volunteer->refresh()->isPending())->toBeTrue();
});

it('returns 403 when the signed url targets another volunteer', function () {
    $volunteer = Volunteer::factory()->create();
    $other = Volunteer::factory()->create();
    $approver = User::factory()->create()->givePermissionTo('Update:Volunteer');
    $url = str_replace($volunteer->id, $other->id, URL::signedRoute('volunteers.approve', ['volunteer' => $volunteer->id]));

    $response = $this->actingAs($approver)->get($url);

    $response->assertForbidden();
    expect($other->refresh()->isPending())->toBeTrue();
});

it('returns 403 for a user without the update permission', function () {
    $volunteer = Volunteer::factory()->create();
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(URL::signedRoute('volunteers.approve', ['volunteer' => $volunteer->id]));

    $response->assertForbidden();
    expect($volunteer->refresh()->isPending())->toBeTrue();
});

it('returns 404 for an unknown volunteer', function () {
    $approver = User::factory()->create()->givePermissionTo('Update:Volunteer');

    $response = $this->actingAs($approver)->get(URL::signedRoute('volunteers.approve', ['volunteer' => (string) Str::uuid()]));

    $response->assertNotFound();
});
