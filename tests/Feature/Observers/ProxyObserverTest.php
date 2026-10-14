<?php

declare(strict_types=1);

use App\Enums\ApprovalStatus;
use App\Filament\Clusters\DemocraticTimes\Resources\Proxies\ProxyResource;
use App\Models\Delegate;
use App\Models\Proxy;
use App\Models\User;
use App\Notifications\ProxyApprovalNotification;
use App\Notifications\ProxyApprovedNotification;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    $this->travelTo('2026-03-10 12:00:00');
});

describe('created', function (): void {
    it('notifies the users who can approve proxies', function (): void {
        $approver = User::factory()->create();
        $approver->givePermissionTo(Permission::findOrCreate('Update:Proxy', 'web'));
        $bystander = User::factory()->create();
        Notification::fake([ProxyApprovalNotification::class]);

        $proxy = Proxy::factory()->create();

        Notification::assertSentTo($approver, ProxyApprovalNotification::class, fn (ProxyApprovalNotification $notification): bool => $notification->proxy->is($proxy));
        Notification::assertNotSentTo($bystander, ProxyApprovalNotification::class);
    });

    it('sends nothing when no user holds the approval permission', function (): void {
        Permission::findOrCreate('Update:Proxy', 'web');
        Notification::fake([ProxyApprovalNotification::class]);

        Proxy::factory()->create();

        Notification::assertNothingSent();
    });

    it('sends nothing when the approval permission does not exist', function (): void {
        Notification::fake([ProxyApprovalNotification::class]);

        Proxy::factory()->create();

        Notification::assertNothingSent();
    });

    it('links the notification mail to the proxies list page', function (): void {
        $proxy = Proxy::factory()->create();

        $mailMessage = (new ProxyApprovalNotification($proxy))->toMail(User::factory()->create());

        expect($mailMessage->actionUrl)->toBe(ProxyResource::getUrl('index'));
    });
});

describe('updated', function (): void {
    it('notifies the current delegate of the delegator seat when the proxy is approved', function (): void {
        $proxy = Proxy::factory()->create();
        $delegateUser = User::factory()->create();
        Delegate::factory()->approved()->for($proxy->delegatorSeat)->for($delegateUser)->create([
            'started_at' => '2026-03-09 12:00:00',
            'ended_at' => null,
        ]);
        Notification::fake([ProxyApprovedNotification::class]);

        $proxy->update(['approval_status' => ApprovalStatus::Approved]);

        Notification::assertSentToTimes($delegateUser, ProxyApprovedNotification::class);
    });

    it('notifies the current delegate exactly once when the proxy is approved through approve()', function (): void {
        $proxy = Proxy::factory()->create();
        $delegateUser = User::factory()->create();
        Delegate::factory()->approved()->for($proxy->delegatorSeat)->for($delegateUser)->create([
            'started_at' => '2026-03-09 12:00:00',
            'ended_at' => null,
        ]);
        Notification::fake([ProxyApprovedNotification::class]);

        $proxy->approve(User::factory()->create()->id);

        Notification::assertSentToTimes($delegateUser, ProxyApprovedNotification::class, 1);
    });

    it('sends nothing when the proxy is rejected', function (): void {
        $proxy = Proxy::factory()->create();
        Delegate::factory()->approved()->for($proxy->delegatorSeat)->create([
            'started_at' => '2026-03-09 12:00:00',
            'ended_at' => null,
        ]);
        Notification::fake([ProxyApprovedNotification::class]);

        $proxy->reject('Invalid proxy request', User::factory()->create()->id);

        Notification::assertNothingSent();
    });

    it('sends nothing when the approval status does not change', function (): void {
        $proxy = Proxy::factory()->approved()->create();
        Delegate::factory()->approved()->for($proxy->delegatorSeat)->create([
            'started_at' => '2026-03-09 12:00:00',
            'ended_at' => null,
        ]);
        Notification::fake([ProxyApprovedNotification::class]);

        $proxy->update(['ended_at' => '2026-03-10 18:00:00']);

        Notification::assertNothingSent();
    });
});
