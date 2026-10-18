<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\DemocraticTimeSession;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

final class ManageSessionSignIns implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $openedCount = 0;
        $closedCount = 0;

        // Auto-open sign-ins
        $sessionsToOpen = DemocraticTimeSession::query()
            ->where('auto_open_sign_in', true)
            ->whereNull('sign_in_opens_at')
            ->whereNotNull('started_at')
            ->get();

        foreach ($sessionsToOpen as $session) {
            if ($session->autoOpenSignIn()) {
                Log::info("Opened sign-in for session: {$session->location} (ID: {$session->id})");
                $openedCount++;
            }
        }

        // Auto-close sign-ins
        $sessionsToClose = DemocraticTimeSession::query()
            ->whereNotNull('sign_in_opens_at')
            ->whereNull('sign_in_closes_at')
            ->whereNotNull('ended_at')
            ->get();

        foreach ($sessionsToClose as $session) {
            if ($session->ended_at->isPast() || $session->ended_at->isSameMinute(now())) {
                $session->autoCloseSignIn();
                Log::info("Closed sign-in for session: {$session->location} (ID: {$session->id})");
                $closedCount++;
            }
        }

        if ($openedCount > 0 || $closedCount > 0) {
            Log::info("Sign-in management completed: {$openedCount} opened, {$closedCount} closed");
        }
    }
}
