<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Models\Contracts\Approvable;
use App\Models\Delegate;

/**
 * Generic approval action. Delegates need ApproveDelegateAction (seat capacity checks).
 */
final class ApproveAction extends BaseApproveAction
{
    protected function supports(Approvable $record): bool
    {
        return ! $record instanceof Delegate;
    }
}
