<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\RelationManagers\BaseDelegatesRelationManager;
use Illuminate\Database\Eloquent\Model;

final class DelegatesRelationManager extends BaseDelegatesRelationManager
{
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('delegate.relation_managers.user_title');
    }

    public function ownerField(): string
    {
        return 'user_id';
    }
}
