<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Models\Contracts\Approvable;
use App\Models\Delegate;

/**
 * Generic bulk approval action. Delegates need BulkApproveDelegatesAction (seat capacity checks).
 */
final class BulkApproveAction extends BaseBulkApproveAction
{
    protected function supports(mixed $record): bool
    {
        return $record instanceof Approvable && ! $record instanceof Delegate;
    }
}
