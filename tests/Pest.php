<?php

declare(strict_types=1);

use App\Services\GoogleWorkspaceServiceInterface;
use FinityLabs\FinMail\Mail\TemplateMail;
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

/**
 * The decoded href of every link in the rendered mail, in document order.
 *
 * @return list<string>
 */
function templateMailLinks(TemplateMail $mail): array
{
    preg_match_all('/href="([^"]+)"/', $mail->render(), $matches);

    return array_map(html_entity_decode(...), $matches[1]);
}
