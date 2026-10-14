<?php

declare(strict_types=1);

namespace App\Filament\Clusters\DemocraticTimes\Resources\Delegates\Pages;

use App\Filament\Clusters\DemocraticTimes\Resources\Delegates\DelegateResource;
use App\Services\DelegateApprovalService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

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
        return app(DelegateApprovalService::class)->createApproved(
            Arr::except($data, 'terminated_delegate_ids'),
            array_values($data['terminated_delegate_ids'] ?? []),
        );
    }
}
