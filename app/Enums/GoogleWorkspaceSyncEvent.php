<?php

declare(strict_types=1);

namespace App\Enums;

enum GoogleWorkspaceSyncEvent: string
{
    case FoundActive = 'found_active';
    case CheckingRemovals = 'checking_removals';
    case AlreadyInGroup = 'already_in_group';
    case Added = 'added';
    case Removed = 'removed';
    case FailedToAdd = 'failed_to_add';
    case FailedToRemove = 'failed_to_remove';
}
