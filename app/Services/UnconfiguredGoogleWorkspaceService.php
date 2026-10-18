<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;

final class UnconfiguredGoogleWorkspaceService implements GoogleWorkspaceServiceInterface
{
    public function isConfigured(): bool
    {
        return false;
    }

    public function addToGroup(string $email): bool
    {
        return $this->skip();
    }

    public function removeFromGroup(string $email): bool
    {
        return $this->skip();
    }

    public function isInGroup(string $email): bool
    {
        return $this->skip();
    }

    private function skip(): bool
    {
        Log::warning('Google Workspace configuration incomplete, skipping delegate sync');

        return false;
    }
}
