<?php

use App\Services\GoogleWorkspaceService;
use Illuminate\Support\Facades\Log;

test('google workspace service can be instantiated', function () {
    $service = new GoogleWorkspaceService(
        '{"type":"service_account"}',
        'admin@afges.org',
        'test-mon@afges.org'
    );

    expect($service)->toBeInstanceOf(GoogleWorkspaceService::class);
});

test('google workspace service handles api errors gracefully when adding to group', function () {
    Log::shouldReceive('error')->once();

    $service = new GoogleWorkspaceService(
        '{"type":"service_account"}',
        'admin@afges.org',
        'test-mon@afges.org'
    );

    // This will fail because we don't have real credentials, but should handle gracefully
    $result = $service->addToGroup('test@example.com');

    expect($result)->toBeFalse();
});

test('google workspace service handles api errors gracefully when removing from group', function () {
    Log::shouldReceive('error')->once();

    $service = new GoogleWorkspaceService(
        '{"type":"service_account"}',
        'admin@afges.org',
        'test-mon@afges.org'
    );

    // This will fail because we don't have real credentials, but should handle gracefully
    $result = $service->removeFromGroup('test@example.com');

    expect($result)->toBeFalse();
});
