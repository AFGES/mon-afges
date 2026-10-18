<?php

declare(strict_types=1);

use App\Services\GoogleWorkspaceServiceInterface;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Mockery\MockInterface;

pest()->extend(Tests\TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->beforeEach(function (): void {
        Http::preventStrayRequests();
        Sleep::fake(syncWithCarbon: true);
    })
    ->in('Feature');

function mockGoogleWorkspaceService(): GoogleWorkspaceServiceInterface&MockInterface
{
    /** @var GoogleWorkspaceServiceInterface&MockInterface $googleWorkspaceService */
    $googleWorkspaceService = test()->mock(GoogleWorkspaceServiceInterface::class);
    $googleWorkspaceService->shouldReceive('isConfigured')->andReturnTrue();

    return $googleWorkspaceService;
}
