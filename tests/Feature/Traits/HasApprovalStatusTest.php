<?php

declare(strict_types=1);

use App\Enums\ApprovalStatus;
use App\Models\Delegate;
use App\Models\Organization;
use App\Models\User;
use App\Models\Volunteer;
use App\Notifications\ApprovalApprovedNotification;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;

dataset('approvable models', [
    'delegate' => Delegate::class,
    'volunteer' => Volunteer::class,
]);

test('approves a pending record on behalf of the authenticated user', function (string $model) {
    Notification::fake();
    travelTo('2026-03-15 12:00:00');
    $approver = User::factory()->create();
    $record = $model::factory()->pending()->create();

    actingAs($approver);

    $result = $record->approve();

    expect($result)->toBeTrue()
        ->and($record->refresh())
        ->isApproved()->toBeTrue()
        ->approved_at->toDateTimeString()->toBe('2026-03-15 12:00:00')
        ->approved_by->toBe($approver->id)
        ->rejection_reason->toBeNull();

    Notification::assertSentTo($record->user, ApprovalApprovedNotification::class);
})->with('approvable models');

test('approves on behalf of the explicit approver rather than the authenticated user', function (string $model) {
    Notification::fake();
    $authenticatedUser = User::factory()->create();
    $explicitApprover = User::factory()->create();
    $record = $model::factory()->pending()->create();

    actingAs($authenticatedUser);

    $record->approve($explicitApprover->id);

    expect($record->refresh()->approved_by)->toBe($explicitApprover->id);
})->with('approvable models');

test('approves a rejected record and clears its rejection reason', function (string $model) {
    Notification::fake();
    $approver = User::factory()->create();
    $record = $model::factory()->rejected()->create();

    $result = $record->approve($approver->id);

    expect($result)->toBeTrue()
        ->and($record->refresh())
        ->isApproved()->toBeTrue()
        ->approved_by->toBe($approver->id)
        ->rejection_reason->toBeNull();

    Notification::assertSentTo($record->user, ApprovalApprovedNotification::class);
})->with('approvable models');

test('does not approve an already approved record', function (string $model) {
    Notification::fake();
    travelTo('2026-03-15 12:00:00');
    $record = $model::factory()->approved()->create(['approved_at' => '2026-03-01 08:00:00']);
    $originalApprover = $record->approved_by;

    $result = $record->approve(User::factory()->create()->id);

    expect($result)->toBeFalse()
        ->and($record->refresh())
        ->approved_at->toDateTimeString()->toBe('2026-03-01 08:00:00')
        ->approved_by->toBe($originalApprover);

    Notification::assertNothingSent();
})->with('approvable models');

test('rejects a pending record with a reason on behalf of the authenticated user', function (string $model) {
    travelTo('2026-03-15 12:00:00');
    $rejecter = User::factory()->create();
    $record = $model::factory()->pending()->create();

    actingAs($rejecter);

    $result = $record->reject('Documents incomplets');

    expect($result)->toBeTrue()
        ->and($record->refresh())
        ->isRejected()->toBeTrue()
        ->approved_at->toDateTimeString()->toBe('2026-03-15 12:00:00')
        ->approved_by->toBe($rejecter->id)
        ->rejection_reason->toBe('Documents incomplets');
})->with('approvable models');

test('rejects on behalf of the explicit rejecter rather than the authenticated user', function (string $model) {
    $authenticatedUser = User::factory()->create();
    $explicitRejecter = User::factory()->create();
    $record = $model::factory()->pending()->create();

    actingAs($authenticatedUser);

    $record->reject('Not eligible', $explicitRejecter->id);

    expect($record->refresh()->approved_by)->toBe($explicitRejecter->id);
})->with('approvable models');

test('rejects an approved record', function (string $model) {
    $record = $model::factory()->approved()->create();

    $result = $record->reject('Revoked');

    expect($result)->toBeTrue()
        ->and($record->refresh())
        ->isRejected()->toBeTrue()
        ->rejection_reason->toBe('Revoked');
})->with('approvable models');

test('does not reject an already rejected record', function (string $model) {
    $record = $model::factory()->rejected()->create(['rejection_reason' => 'Original reason']);

    $result = $record->reject('New reason');

    expect($result)->toBeFalse()
        ->and($record->refresh()->rejection_reason)->toBe('Original reason');
})->with('approvable models');

test('reports a new record as pending only', function (string $model) {
    $record = $model::factory()->pending()->create();

    expect($record)
        ->isPending()->toBeTrue()
        ->isApproved()->toBeFalse()
        ->isRejected()->toBeFalse();
})->with('approvable models');

test('returns only pending records when filtering pending', function () {
    $pending1 = Volunteer::factory()->pending()->create();

    $pending2 = Volunteer::factory()->pending()->create();

    $approved = Volunteer::factory()->approved()->create();
    $rejected = Volunteer::factory()->rejected()->create();

    $results = Volunteer::withoutGlobalScopes()
        ->pending()
        ->get();

    expect($results)->toHaveCount(2);
    expect($results->pluck('id'))->toContain($pending1->id, $pending2->id);
    expect($results->pluck('id'))->not->toContain($approved->id, $rejected->id);
});

test('returns only approved records when filtering approved', function () {
    $approved1 = Volunteer::factory()->approved()->create();
    $approved2 = Volunteer::factory()->approved()->create();

    $pending = Volunteer::factory()->pending()->create();
    $rejected = Volunteer::factory()->rejected()->create();

    $results = Volunteer::withoutGlobalScopes()
        ->approved()
        ->get();

    expect($results)->toHaveCount(2);
    expect($results->pluck('id'))->toContain($approved1->id, $approved2->id);
    expect($results->pluck('id'))->not->toContain($pending->id, $rejected->id);
});

test('returns only rejected records when filtering rejected', function () {
    $rejected1 = Volunteer::factory()->rejected()->create();
    $rejected2 = Volunteer::factory()->rejected()->create();

    $pending = Volunteer::factory()->pending()->create();
    $approved = Volunteer::factory()->approved()->create();

    $results = Volunteer::withoutGlobalScopes()
        ->rejected()
        ->get();

    expect($results)->toHaveCount(2);
    expect($results->pluck('id'))->toContain($rejected1->id, $rejected2->id);
    expect($results->pluck('id'))->not->toContain($pending->id, $approved->id);
});

test('returns only records with the given status when filtering by approval status', function () {
    $pending = Volunteer::factory()->pending()->create();
    $approved = Volunteer::factory()->approved()->create();
    $rejected = Volunteer::factory()->rejected()->create();

    $pendingResults = Volunteer::withoutGlobalScopes()
        ->withApprovalStatus(ApprovalStatus::Pending)
        ->get();

    expect($pendingResults)->toHaveCount(1);
    expect($pendingResults->first()->id)->toBe($pending->id);

    $approvedResults = Volunteer::withoutGlobalScopes()
        ->withApprovalStatus(ApprovalStatus::Approved)
        ->get();

    expect($approvedResults)->toHaveCount(1);
    expect($approvedResults->first()->id)->toBe($approved->id);

    $rejectedResults = Volunteer::withoutGlobalScopes()
        ->withApprovalStatus(ApprovalStatus::Rejected)
        ->get();

    expect($rejectedResults)->toHaveCount(1);
    expect($rejectedResults->first()->id)->toBe($rejected->id);
});

test('returns only records approved by the given user when filtering approved by a user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $approvedByUser1 = Volunteer::factory()->approved()->create([
        'approved_by' => $user1->id,
    ]);

    $alsoApprovedByUser1 = Volunteer::factory()->approved()->create([
        'approved_by' => $user1->id,
    ]);

    $approvedByUser2 = Volunteer::factory()->approved()->create([
        'approved_by' => $user2->id,
    ]);
    $rejectedByUser1 = Volunteer::factory()->rejected()->create([
        'approved_by' => $user1->id,
    ]);

    $results = Volunteer::withoutGlobalScopes()
        ->approvedBy($user1->id)
        ->get();

    expect($results)->toHaveCount(2);
    expect($results->pluck('id'))->toContain($approvedByUser1->id, $alsoApprovedByUser1->id);
    expect($results->pluck('id'))->not->toContain($approvedByUser2->id, $rejectedByUser1->id);
});

test('returns only records rejected by the given user when filtering rejected by a user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $rejectedByUser1 = Volunteer::factory()->rejected()->create([
        'approved_by' => $user1->id,
    ]);

    $alsoRejectedByUser1 = Volunteer::factory()->rejected()->create([
        'approved_by' => $user1->id,
    ]);

    $rejectedByUser2 = Volunteer::factory()->rejected()->create([
        'approved_by' => $user2->id,
    ]);
    $approvedByUser1 = Volunteer::factory()->approved()->create([
        'approved_by' => $user1->id,
    ]);

    $results = Volunteer::withoutGlobalScopes()
        ->rejectedBy($user1->id)
        ->get();

    expect($results)->toHaveCount(2);
    expect($results->pluck('id'))->toContain($rejectedByUser1->id, $alsoRejectedByUser1->id);
    expect($results->pluck('id'))->not->toContain($rejectedByUser2->id, $approvedByUser1->id);
});

test('returns only records matching both the approval status and other query constraints', function () {
    $org1 = Organization::factory()->create();
    $org2 = Organization::factory()->create();

    $pending = Volunteer::factory()->pending()->create([
        'organization_id' => $org1->id,
    ]);

    $approved = Volunteer::factory()->approved()->create([
        'organization_id' => $org1->id,
    ]);

    $approvedOtherOrg = Volunteer::factory()->approved()->create([
        'organization_id' => $org2->id,
    ]);

    $results = Volunteer::withoutGlobalScopes()
        ->approved()
        ->where('organization_id', $org1->id)
        ->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($approved->id);
});

test('returns only records matching both the approval status and the currently active period', function () {
    travelTo('2026-03-15 12:00:00');
    $currentApproved = Volunteer::factory()->approved()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => null,
    ]);
    $currentPending = Volunteer::factory()->pending()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => null,
    ]);
    $endedApproved = Volunteer::factory()->approved()->create([
        'started_at' => now()->subDays(10),
        'ended_at' => now()->subDays(2),
    ]);

    $results = Volunteer::withoutGlobalScopes()
        ->approved()
        ->currentlyActive()
        ->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($currentApproved->id);
});

test('returns records approved by the user when the user id is given as a string', function () {
    $user = User::factory()->create();

    $approved = Volunteer::factory()->approved()->create([
        'approved_by' => $user->id,
    ]);

    $results = Volunteer::withoutGlobalScopes()
        ->approvedBy((string) $user->id)
        ->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($approved->id);
});

test('returns records rejected by the user when the user id is given as a string', function () {
    $user = User::factory()->create();

    $rejected = Volunteer::factory()->rejected()->create([
        'approved_by' => $user->id,
    ]);

    $results = Volunteer::withoutGlobalScopes()
        ->rejectedBy((string) $user->id)
        ->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($rejected->id);
});
