<?php

declare(strict_types=1);

use App\Filament\Resources\Organizations\OrganizationResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Organization;
use App\Models\User;

beforeEach(function () {
    // Create a user with permission to access the panel
    $this->user = User::factory()->create([
        'email' => 'admin@afges.org',
    ]);
    $this->actingAs($this->user);
});

test('user resource has record title attribute', function () {
    expect(UserResource::getRecordTitle(
        User::factory()->create(['name' => 'John Doe'])
    ))->toBe('John Doe');
});

test('organization resource has record title attribute', function () {
    expect(OrganizationResource::getRecordTitle(
        Organization::factory()->create(['short_name' => 'AFGES'])
    ))->toBe('AFGES');
});

test('user resource has globally searchable attributes', function () {
    $attributes = UserResource::getGloballySearchableAttributes();

    expect($attributes)->toContain('name')
        ->and($attributes)->toContain('email');
});

test('organization resource has globally searchable attributes', function () {
    $attributes = OrganizationResource::getGloballySearchableAttributes();

    expect($attributes)->toContain('short_name')
        ->and($attributes)->toContain('legal_name')
        ->and($attributes)->toContain('domain');
});

test('user resource returns global search result details', function () {
    $user = User::factory()->create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]);

    $details = UserResource::getGlobalSearchResultDetails($user);

    expect($details)->toHaveKey(__('user.email'))
        ->and($details[__('user.email')])->toBe('jane@example.com');
});

test('organization resource returns global search result details', function () {
    $organization = Organization::factory()->create([
        'short_name' => 'AFGES',
        'legal_name' => 'Association des Étudiants',
        'domain' => 'afges.org',
    ]);

    $details = OrganizationResource::getGlobalSearchResultDetails($organization);

    expect($details)->toHaveKey(__('organization.fields.legal_name'))
        ->and($details[__('organization.fields.legal_name')])->toBe('Association des Étudiants')
        ->and($details)->toHaveKey(__('organization.fields.domain'))
        ->and($details[__('organization.fields.domain')])->toBe('afges.org');
});
