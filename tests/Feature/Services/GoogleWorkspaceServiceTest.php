<?php

declare(strict_types=1);

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

    $serviceAccountJson = json_encode([
        'type' => 'service_account',
        'project_id' => 'test-project',
        'private_key_id' => 'test-key-id',
        'private_key' => '-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQC7W\n-----END PRIVATE KEY-----\n',
        'client_email' => 'test@test-project.iam.gserviceaccount.com',
        'client_id' => '123456789',
        'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
        'token_uri' => 'https://oauth2.googleapis.com/token',
        'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
    ]);

    $service = new GoogleWorkspaceService(
        $serviceAccountJson,
        'admin@afges.org',
        'test-mon@afges.org'
    );

    // This will fail because we don't have real credentials, but should handle gracefully
    $result = $service->addToGroup('test@example.com');

    expect($result)->toBeFalse();
});

test('google workspace service handles api errors gracefully when removing from group', function () {
    Log::shouldReceive('error')->once();

    $serviceAccountJson = json_encode([
        'type' => 'service_account',
        'project_id' => 'test-project',
        'private_key_id' => 'test-key-id',
        'private_key' => '-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQC7W\n-----END PRIVATE KEY-----\n',
        'client_email' => 'test@test-project.iam.gserviceaccount.com',
        'client_id' => '123456789',
        'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
        'token_uri' => 'https://oauth2.googleapis.com/token',
        'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
    ]);

    $service = new GoogleWorkspaceService(
        $serviceAccountJson,
        'admin@afges.org',
        'test-mon@afges.org'
    );

    // This will fail because we don't have real credentials, but should handle gracefully
    $result = $service->removeFromGroup('test@example.com');

    expect($result)->toBeFalse();
});
