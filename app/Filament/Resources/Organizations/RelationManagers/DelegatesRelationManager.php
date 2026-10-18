<?php

declare(strict_types=1);

namespace App\Filament\Resources\Organizations\RelationManagers;

use App\Filament\RelationManagers\BaseDelegatesRelationManager;
use Illuminate\Database\Eloquent\Model;

final class DelegatesRelationManager extends BaseDelegatesRelationManager
{
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('delegate.relation_managers.organization_title');
    }

    public function ownerField(): string
    {
        return 'organization_id';
    }
}
