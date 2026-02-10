<?php

declare(strict_types=1);

use App\Enums\ApprovalStatus;
use App\Models\User;
use App\Models\Volunteer;

it('pending scope filters pending records', function () {
    $pending1 = Volunteer::factory()->create([
        'approval_status' => ApprovalStatus::Pending,
    ]);

    $pending2 = Volunteer::factory()->create([
        'approval_status' => ApprovalStatus::Pending,
    ]);

    $approved = Volunteer::factory()->approved()->create();
    $rejected = Volunteer::factory()->rejected()->create();

    $results = Volunteer::withoutGlobalScopes()
        ->pending()
        ->get();

    expect($results)->toHaveCount(2);
    expect($results->pluck('id'))->toContain($pending1->id, $pending2->id);
    expect($results->pluck('id'))->not->toContain($approved->id, $rejected->id);
});

it('approved scope filters approved records', function () {
    $approved1 = Volunteer::factory()->approved()->create();
    $approved2 = Volunteer::factory()->approved()->create();

    $pending = Volunteer::factory()->create([
        'approval_status' => ApprovalStatus::Pending,
    ]);
    $rejected = Volunteer::factory()->rejected()->create();

    $results = Volunteer::withoutGlobalScopes()
        ->approved()
        ->get();

    expect($results)->toHaveCount(2);
    expect($results->pluck('id'))->toContain($approved1->id, $approved2->id);
    expect($results->pluck('id'))->not->toContain($pending->id, $rejected->id);
});

it('rejected scope filters rejected records', function () {
    $rejected1 = Volunteer::factory()->rejected()->create();
    $rejected2 = Volunteer::factory()->rejected('Different reason')->create();

    $pending = Volunteer::factory()->create([
        'approval_status' => ApprovalStatus::Pending,
    ]);
    $approved = Volunteer::factory()->approved()->create();

    $results = Volunteer::withoutGlobalScopes()
        ->rejected()
        ->get();

    expect($results)->toHaveCount(2);
    expect($results->pluck('id'))->toContain($rejected1->id, $rejected2->id);
    expect($results->pluck('id'))->not->toContain($pending->id, $approved->id);
});

it('withApprovalStatus scope filters by specific status', function () {
    $pending = Volunteer::factory()->create([
        'approval_status' => ApprovalStatus::Pending,
    ]);
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

it('approvedBy scope filters records approved by specific user', function () {
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

    // Rejected by user1 (should not be included)
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

it('rejectedBy scope filters records rejected by specific user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $rejectedByUser1 = Volunteer::factory()->rejected('Reason 1')->create([
        'approved_by' => $user1->id,
    ]);

    $alsoRejectedByUser1 = Volunteer::factory()->rejected('Reason 2')->create([
        'approved_by' => $user1->id,
    ]);

    $rejectedByUser2 = Volunteer::factory()->rejected('Reason 3')->create([
        'approved_by' => $user2->id,
    ]);

    // Approved by user1 (should not be included)
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

it('scopes can be combined with other query constraints', function () {
    $org1 = \App\Models\Organization::factory()->create();
    $org2 = \App\Models\Organization::factory()->create();

    $pending = Volunteer::factory()->create([
        'organization_id' => $org1->id,
        'approval_status' => ApprovalStatus::Pending,
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

it('scopes can be combined with period scopes', function () {
    // Currently active and approved
    $currentApproved = Volunteer::factory()->approved()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => null,
    ]);

    // Currently active but pending
    $currentPending = Volunteer::factory()->create([
        'started_at' => now()->subDays(5),
        'ended_at' => null,
        'approval_status' => ApprovalStatus::Pending,
    ]);

    // Ended but approved
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

it('multiple approval scopes cannot be combined meaningfully', function () {
    Volunteer::factory()->approved()->create();
    Volunteer::factory()->rejected()->create();
    Volunteer::factory()->create(['approval_status' => ApprovalStatus::Pending]);

    // This should return no results as a record can't be both approved and rejected
    $results = Volunteer::withoutGlobalScopes()
        ->approved()
        ->rejected()
        ->get();

    expect($results)->toHaveCount(0);
});

it('approvedBy scope accepts string user id', function () {
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

it('rejectedBy scope accepts string user id', function () {
    $user = User::factory()->create();

    $rejected = Volunteer::factory()->rejected('Test reason')->create([
        'approved_by' => $user->id,
    ]);

    $results = Volunteer::withoutGlobalScopes()
        ->rejectedBy((string) $user->id)
        ->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($rejected->id);
});

it('scopes work with pagination', function () {
    Volunteer::factory()->count(15)->approved()->create();
    Volunteer::factory()->count(10)->create(['approval_status' => ApprovalStatus::Pending]);

    $results = Volunteer::withoutGlobalScopes()
        ->approved()
        ->paginate(10);

    expect($results->total())->toBe(15);
    expect($results->perPage())->toBe(10);
    expect($results->count())->toBe(10);
});

it('scopes work with aggregations', function () {
    $org = \App\Models\Organization::factory()->create();

    Volunteer::factory()->count(5)->approved()->create(['organization_id' => $org->id]);
    Volunteer::factory()->count(3)->create([
        'organization_id' => $org->id,
        'approval_status' => ApprovalStatus::Pending,
    ]);

    $approvedCount = Volunteer::withoutGlobalScopes()
        ->approved()
        ->where('organization_id', $org->id)
        ->count();

    expect($approvedCount)->toBe(5);
});

it('scopes work with soft deletes', function () {
    $approved = Volunteer::factory()->approved()->create();
    $deleted = Volunteer::factory()->approved()->create();
    $deleted->delete();

    // Without global scopes, soft deletes are also removed, so we see both
    $resultsWithoutScopes = Volunteer::withoutGlobalScopes()
        ->approved()
        ->get();

    expect($resultsWithoutScopes)->toHaveCount(2);

    // With only ApprovedScope removed, soft deletes still work
    $results = Volunteer::withoutGlobalScope(\App\Models\Scopes\ApprovedScope::class)
        ->approved()
        ->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($approved->id);
});
