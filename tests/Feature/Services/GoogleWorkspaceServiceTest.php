<?php

declare(strict_types=1);

use App\Services\GoogleWorkspaceService;
use Google\Client;
use Google\Service\Directory;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;

/**
 * @param  array<int, Response|Throwable>  $queue
 * @param  array<int, array<string, mixed>>  $history
 */
function googleWorkspaceServiceReplying(array $queue, array &$history = []): GoogleWorkspaceService
{
    $stack = HandlerStack::create(new MockHandler($queue));
    $stack->push(Middleware::history($history));

    $client = new Client;
    $client->setHttpClient(new GuzzleClient(['handler' => $stack]));

    return new GoogleWorkspaceService(
        '{"type":"service_account"}',
        'admin@afges.org',
        'test-mon@afges.org',
        new Directory($client),
    );
}

function googleErrorResponse(int $status, string $message): Response
{
    return new Response($status, ['Content-Type' => 'application/json'], json_encode([
        'error' => ['code' => $status, 'message' => $message],
    ]));
}

function googleMemberResponse(): Response
{
    return new Response(200, ['Content-Type' => 'application/json'], json_encode([
        'email' => 'delegate@afges.org',
        'role' => 'MEMBER',
    ]));
}

beforeEach(function () {
    Log::spy();
});

describe('addToGroup', function () {
    it('inserts the email as a group member', function () {
        $history = [];
        $service = googleWorkspaceServiceReplying([googleMemberResponse()], $history);

        $result = $service->addToGroup('delegate@afges.org');

        /** @var Request $request */
        $request = $history[0]['request'];
        expect($result)->toBeTrue()
            ->and($request->getMethod())->toBe('POST')
            ->and($request->getUri()->getPath())->toEndWith('/groups/test-mon%40afges.org/members')
            ->and(json_decode((string) $request->getBody(), true))->toBe([
                'email' => 'delegate@afges.org',
                'role' => 'MEMBER',
            ]);
    });

    it('treats a 409 conflict as success because the user is already a member', function () {
        $service = googleWorkspaceServiceReplying([googleErrorResponse(409, 'Member already exists.')]);

        $result = $service->addToGroup('delegate@afges.org');

        expect($result)->toBeTrue();
        Log::shouldNotHaveReceived('error');
    });

    it('returns false and logs an error when the API rejects the request', function () {
        $service = googleWorkspaceServiceReplying([googleErrorResponse(500, 'Backend Error')]);

        $result = $service->addToGroup('delegate@afges.org');

        expect($result)->toBeFalse();
        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(fn (string $message, array $context): bool => $message === 'Failed to add user to Google Workspace delegate group'
                && $context['email'] === 'delegate@afges.org'
                && $context['group'] === 'test-mon@afges.org');
    });

    it('returns false and logs an error when the connection fails', function () {
        $service = googleWorkspaceServiceReplying([
            new ConnectException('Connection refused', new Request('POST', 'https://admin.googleapis.com')),
        ]);

        $result = $service->addToGroup('delegate@afges.org');

        expect($result)->toBeFalse();
        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(fn (string $message): bool => $message === 'Unexpected error adding user to Google Workspace delegate group');
    });
});

describe('removeFromGroup', function () {
    it('deletes the group member', function () {
        $history = [];
        $service = googleWorkspaceServiceReplying([new Response(204)], $history);

        $result = $service->removeFromGroup('delegate@afges.org');

        /** @var Request $request */
        $request = $history[0]['request'];
        expect($result)->toBeTrue()
            ->and($request->getMethod())->toBe('DELETE')
            ->and($request->getUri()->getPath())->toEndWith('/groups/test-mon%40afges.org/members/delegate%40afges.org');
    });

    it('treats a 404 as success because the user is not a member', function () {
        $service = googleWorkspaceServiceReplying([googleErrorResponse(404, 'Resource Not Found: memberKey')]);

        $result = $service->removeFromGroup('delegate@afges.org');

        expect($result)->toBeTrue();
        Log::shouldNotHaveReceived('error');
    });

    it('returns false and logs an error when the API rejects the request', function () {
        $service = googleWorkspaceServiceReplying([googleErrorResponse(403, 'Not Authorized')]);

        $result = $service->removeFromGroup('delegate@afges.org');

        expect($result)->toBeFalse();
        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(fn (string $message): bool => $message === 'Failed to remove user from Google Workspace delegate group');
    });

    it('returns false and logs an error when the connection fails', function () {
        $service = googleWorkspaceServiceReplying([
            new ConnectException('Connection refused', new Request('DELETE', 'https://admin.googleapis.com')),
        ]);

        $result = $service->removeFromGroup('delegate@afges.org');

        expect($result)->toBeFalse();
        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(fn (string $message): bool => $message === 'Unexpected error removing user from Google Workspace delegate group');
    });
});

describe('isInGroup', function () {
    it('returns true when the member exists', function () {
        $history = [];
        $service = googleWorkspaceServiceReplying([googleMemberResponse()], $history);

        $result = $service->isInGroup('delegate@afges.org');

        /** @var Request $request */
        $request = $history[0]['request'];
        expect($result)->toBeTrue()
            ->and($request->getMethod())->toBe('GET')
            ->and($request->getUri()->getPath())->toEndWith('/groups/test-mon%40afges.org/members/delegate%40afges.org');
    });

    it('returns false without logging an error when the member is not found', function () {
        $service = googleWorkspaceServiceReplying([googleErrorResponse(404, 'Resource Not Found: memberKey')]);

        $result = $service->isInGroup('delegate@afges.org');

        expect($result)->toBeFalse();
        Log::shouldNotHaveReceived('error');
    });

    it('returns false and logs an error when the API fails', function () {
        $service = googleWorkspaceServiceReplying([googleErrorResponse(500, 'Backend Error')]);

        $result = $service->isInGroup('delegate@afges.org');

        expect($result)->toBeFalse();
        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(fn (string $message): bool => $message === 'Failed to check Google Workspace delegate group membership');
    });

    it('returns false and logs an error when the connection fails', function () {
        $service = googleWorkspaceServiceReplying([
            new ConnectException('Connection refused', new Request('GET', 'https://admin.googleapis.com')),
        ]);

        $result = $service->isInGroup('delegate@afges.org');

        expect($result)->toBeFalse();
        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(fn (string $message): bool => $message === 'Unexpected error checking Google Workspace delegate group membership');
    });
});
