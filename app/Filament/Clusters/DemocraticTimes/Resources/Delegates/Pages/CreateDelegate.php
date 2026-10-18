<?php

declare(strict_types=1);

namespace App\Filament\Clusters\DemocraticTimes\Resources\Delegates\Pages;

use App\Filament\Clusters\DemocraticTimes\Resources\Delegates\DelegateResource;
use App\Filament\RelationManagers\BaseDelegatesRelationManager;
use App\Services\DelegateApprovalService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateDelegate extends CreateRecord
{
    protected static string $resource = DelegateResource::class;

    /**
     * Delegates created by an administrator are approved right away, ending the selected current delegates of the seat.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return BaseDelegatesRelationManager::createApproved($data, app(DelegateApprovalService::class));
    }
}
