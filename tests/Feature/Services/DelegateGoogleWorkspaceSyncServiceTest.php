<?php

declare(strict_types=1);

use App\Models\Delegate;
use App\Models\User;
use App\Services\DelegateGoogleWorkspaceSyncService;
use App\Services\GoogleWorkspaceServiceInterface;
use Illuminate\Support\Facades\Log;
use Mockery\MockInterface;

function syncServiceWith(MockInterface $googleWorkspaceService): DelegateGoogleWorkspaceSyncService
{
    return new DelegateGoogleWorkspaceSyncService($googleWorkspaceService);
}

function googleWorkspaceMock(): MockInterface
{
    return mock(GoogleWorkspaceServiceInterface::class);
}

describe('shouldSync', function () {
    it('accepts a current delegate with a verified email', function () {
        $delegate = Delegate::factory()->verified()->make(['ended_at' => null]);

        expect(syncServiceWith(googleWorkspaceMock())->shouldSync($delegate))->toBeTrue();
    });

    it('rejects a delegate without email', function () {
        $delegate = Delegate::factory()->verified()->make(['email' => null, 'ended_at' => null]);

        expect(syncServiceWith(googleWorkspaceMock())->shouldSync($delegate))->toBeFalse();
    });

    it('rejects a delegate with an unverified email', function () {
        $delegate = Delegate::factory()->unverified()->make(['ended_at' => null]);

        expect(syncServiceWith(googleWorkspaceMock())->shouldSync($delegate))->toBeFalse();
    });

    it('rejects a delegate whose mandate has ended', function () {
        $delegate = Delegate::factory()->verified()->make([
            'started_at' => now()->subYear(),
            'ended_at' => now()->subDay(),
        ]);

        expect(syncServiceWith(googleWorkspaceMock())->shouldSync($delegate))->toBeFalse();
    });
});

describe('addDelegate', function () {
    it('adds the delegate email to the group', function () {
        $delegate = Delegate::factory()->verified()->make(['email' => 'delegate@afges.org', 'ended_at' => null]);
        $google = googleWorkspaceMock();
        $google->shouldReceive('addToGroup')->once()->with('delegate@afges.org')->andReturnTrue();

        expect(syncServiceWith($google)->addDelegate($delegate))->toBeTrue();
    });

    it('returns false when Google fails to add the email', function () {
        $delegate = Delegate::factory()->verified()->make(['email' => 'delegate@afges.org', 'ended_at' => null]);
        $google = googleWorkspaceMock();
        $google->shouldReceive('addToGroup')->once()->with('delegate@afges.org')->andReturnFalse();

        expect(syncServiceWith($google)->addDelegate($delegate))->toBeFalse();
    });

    it('does not call Google and logs a warning when the delegate does not qualify', function (array $attributes, array $expectedContext) {
        Log::spy();
        $delegate = Delegate::factory()->make(['ended_at' => null, ...$attributes]);
        $google = googleWorkspaceMock();
        $google->shouldNotReceive('addToGroup');

        $result = syncServiceWith($google)->addDelegate($delegate);

        expect($result)->toBeFalse();
        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn (string $message, array $context): bool => $message === 'Cannot sync delegate to Google Workspace: requirements not met'
                && array_intersect_assoc($expectedContext, $context) === $expectedContext);
    })->with([
        'no email' => [['email' => null, 'email_verified_at' => now()], ['has_email' => false]],
        'unverified email' => [['email' => 'a@afges.org', 'email_verified_at' => null], ['email_verified' => false]],
        'ended mandate' => [
            ['email' => 'a@afges.org', 'email_verified_at' => now(), 'started_at' => now()->subYear(), 'ended_at' => now()->subDay()],
            ['is_active' => false],
        ],
    ]);

    it('skips the call and succeeds when another active delegate uses the same email', function () {
        Log::spy();
        Delegate::factory()->approved()->verified()->create(['email' => 'shared@afges.org', 'ended_at' => null]);
        $delegate = Delegate::factory()->verified()->make(['email' => 'shared@afges.org', 'ended_at' => null]);
        $google = googleWorkspaceMock();
        $google->shouldNotReceive('addToGroup');

        $result = syncServiceWith($google)->addDelegate($delegate);

        expect($result)->toBeTrue();
        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(fn (string $message, array $context): bool => $message === 'Skipping Google Workspace add: email already in use by other active delegates'
                && $context['email'] === 'shared@afges.org');
    });
});

describe('removeDelegate', function () {
    it('removes the delegate email from the group', function () {
        $delegate = Delegate::factory()->create(['email' => 'delegate@afges.org']);
        $google = googleWorkspaceMock();
        $google->shouldReceive('removeFromGroup')->once()->with('delegate@afges.org')->andReturnTrue();

        expect(syncServiceWith($google)->removeDelegate($delegate))->toBeTrue();
    });

    it('removes the email of an ended mandate', function () {
        $delegate = Delegate::factory()->create([
            'email' => 'delegate@afges.org',
            'started_at' => now()->subYear(),
            'ended_at' => now()->subDay(),
        ]);
        $google = googleWorkspaceMock();
        $google->shouldReceive('removeFromGroup')->once()->with('delegate@afges.org')->andReturnTrue();

        expect(syncServiceWith($google)->removeDelegate($delegate))->toBeTrue();
    });

    it('returns false when Google fails to remove the email', function () {
        $delegate = Delegate::factory()->create(['email' => 'delegate@afges.org']);
        $google = googleWorkspaceMock();
        $google->shouldReceive('removeFromGroup')->once()->with('delegate@afges.org')->andReturnFalse();

        expect(syncServiceWith($google)->removeDelegate($delegate))->toBeFalse();
    });

    it('does not call Google and logs a warning when the delegate has no email', function () {
        Log::spy();
        $delegate = Delegate::factory()->make(['email' => null]);
        $google = googleWorkspaceMock();
        $google->shouldNotReceive('removeFromGroup');

        $result = syncServiceWith($google)->removeDelegate($delegate);

        expect($result)->toBeFalse();
        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn (string $message, array $context): bool => $message === 'Cannot remove delegate from Google Workspace: email missing'
                && $context['delegate_id'] === $delegate->id);
    });

    it('skips the call and succeeds while another active delegate uses the same email', function () {
        Log::spy();
        $remaining = Delegate::factory()->approved()->verified()->create(['email' => 'shared@afges.org', 'ended_at' => null]);
        $delegate = Delegate::factory()->create(['email' => 'shared@afges.org']);
        $google = googleWorkspaceMock();
        $google->shouldNotReceive('removeFromGroup');

        $result = syncServiceWith($google)->removeDelegate($delegate);

        expect($result)->toBeTrue();
        $this->assertModelExists($remaining);
        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(fn (string $message, array $context): bool => $message === 'Skipping Google Workspace removal: email still in use by other active delegates'
                && $context['delegate_id'] === $delegate->id
                && $context['email'] === 'shared@afges.org');
    });
});

describe('getActiveDelegateEmails', function () {
    it('lists distinct emails of approved, current delegates with a verified email', function () {
        Delegate::factory()->approved()->verified()->create(['email' => 'first@afges.org', 'ended_at' => null]);
        Delegate::factory()->approved()->verified()->create(['email' => 'first@afges.org', 'ended_at' => null]);
        Delegate::factory()->approved()->verified()->create(['email' => 'second@afges.org', 'ended_at' => null]);
        Delegate::factory()->approved()->unverified()->create(['email' => 'unverified@afges.org', 'ended_at' => null]);
        Delegate::factory()->verified()->create(['email' => 'pending@afges.org', 'ended_at' => null]);
        Delegate::factory()->approved()->verified()->create([
            'email' => 'ended@afges.org',
            'started_at' => now()->subYear(),
            'ended_at' => now()->subDay(),
        ]);

        $emails = syncServiceWith(googleWorkspaceMock())->getActiveDelegateEmails();

        expect($emails->sort()->values()->all())->toBe(['first@afges.org', 'second@afges.org']);
    });
});

describe('group membership delegation', function () {
    it('forwards isInGroup, addToGroup and removeFromGroup to Google', function () {
        $google = googleWorkspaceMock();
        $google->shouldReceive('isInGroup')->once()->with('a@afges.org')->andReturnTrue();
        $google->shouldReceive('addToGroup')->once()->with('b@afges.org')->andReturnFalse();
        $google->shouldReceive('removeFromGroup')->once()->with('c@afges.org')->andReturnTrue();
        $service = syncServiceWith($google);

        expect($service->isInGroup('a@afges.org'))->toBeTrue()
            ->and($service->addToGroup('b@afges.org'))->toBeFalse()
            ->and($service->removeFromGroup('c@afges.org'))->toBeTrue();
    });
});

describe('syncAll', function () {
    it('adds missing delegates, keeps present ones and removes former delegates', function () {
        Delegate::factory()->approved()->verified()->create(['email' => 'present@afges.org', 'ended_at' => null]);
        Delegate::factory()->approved()->verified()->create(['email' => 'missing@afges.org', 'ended_at' => null]);
        Delegate::factory()->approved()->verified()->create(['email' => 'rejected@afges.org', 'ended_at' => null]);
        User::factory()->create(['email' => 'former@afges.org']);
        User::factory()->create(['email' => 'never-member@afges.org']);
        $google = googleWorkspaceMock();
        $google->shouldReceive('isInGroup')->with('present@afges.org')->andReturnTrue();
        $google->shouldReceive('isInGroup')->with('missing@afges.org')->andReturnFalse();
        $google->shouldReceive('isInGroup')->with('rejected@afges.org')->andReturnFalse();
        $google->shouldReceive('isInGroup')->with('former@afges.org')->andReturnTrue();
        $google->shouldReceive('isInGroup')->andReturnFalse();
        $google->shouldReceive('addToGroup')->with('missing@afges.org')->once()->andReturnTrue();
        $google->shouldReceive('addToGroup')->with('rejected@afges.org')->once()->andReturnFalse();
        $google->shouldReceive('removeFromGroup')->with('former@afges.org')->once()->andReturnFalse();

        $stats = syncServiceWith($google)->syncAll();

        expect($stats)->toBe([
            'already_in_group' => 1,
            'added' => 1,
            'removed' => 0,
            'failed_to_add' => 1,
            'failed_to_remove' => 1,
        ]);
    });

    it('reports the changes without applying them in dry-run mode', function () {
        Delegate::factory()->approved()->verified()->create(['email' => 'missing@afges.org', 'ended_at' => null]);
        User::factory()->create(['email' => 'former@afges.org']);
        $google = googleWorkspaceMock();
        $google->shouldReceive('isInGroup')->with('missing@afges.org')->andReturnFalse();
        $google->shouldReceive('isInGroup')->with('former@afges.org')->andReturnTrue();
        $google->shouldReceive('isInGroup')->andReturnFalse();
        $google->shouldNotReceive('addToGroup');
        $google->shouldNotReceive('removeFromGroup');

        $stats = syncServiceWith($google)->syncAll(dryRun: true);

        expect($stats)->toBe([
            'already_in_group' => 0,
            'added' => 1,
            'removed' => 1,
            'failed_to_add' => 0,
            'failed_to_remove' => 0,
        ]);
    });
});
