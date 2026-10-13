<?php

declare(strict_types=1);

use App\Services\GoogleWorkspaceService;
use App\Services\GoogleWorkspaceServiceInterface;
use App\Services\UnconfiguredGoogleWorkspaceService;
use Illuminate\Support\Facades\Log;

it('answers false and logs a warning for every operation', function (string $method) {
    Log::spy();

    $result = (new UnconfiguredGoogleWorkspaceService)->{$method}('delegate@afges.org');

    expect($result)->toBeFalse();
    Log::shouldHaveReceived('warning')->once()->with('Google Workspace configuration incomplete, skipping delegate sync');
})->with(['addToGroup', 'removeFromGroup', 'isInGroup']);

it('resolves the unconfigured service when the Google Workspace configuration is incomplete', function (string $missingKey) {
    config([
        'services.google.workspace.service_account_json' => '{"type":"service_account"}',
        'services.google.workspace.admin_email' => 'admin@afges.org',
        'services.google.workspace.delegate_group' => 'test-mon@afges.org',
        "services.google.workspace.{$missingKey}" => null,
    ]);

    expect(app(GoogleWorkspaceServiceInterface::class))->toBeInstanceOf(UnconfiguredGoogleWorkspaceService::class);
})->with(['service_account_json', 'admin_email', 'delegate_group']);

it('resolves the real service when the Google Workspace configuration is complete', function () {
    config([
        'services.google.workspace.service_account_json' => '{"type":"service_account"}',
        'services.google.workspace.admin_email' => 'admin@afges.org',
        'services.google.workspace.delegate_group' => 'test-mon@afges.org',
    ]);

    expect(app(GoogleWorkspaceServiceInterface::class))->toBeInstanceOf(GoogleWorkspaceService::class);
});
