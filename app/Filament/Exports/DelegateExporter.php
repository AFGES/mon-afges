<?php

declare(strict_types=1);

namespace App\Filament\Exports;

use App\Models\Delegate;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

/**
 * Exports delegates with the columns and headers of the import, so an exported file can be imported again unchanged.
 */
final class DelegateExporter extends Exporter
{
    protected static ?string $model = Delegate::class;

    /**
     * @return array<ExportColumn>
     */
    public static function getColumns(): array
    {
        return [
            ExportColumn::make('email')
                ->label('email')
                ->state(fn (Delegate $record): string => $record->user->email),
            ExportColumn::make('first_name')
                ->label('first_name')
                ->state(fn (Delegate $record): ?string => $record->user->first_name),
            ExportColumn::make('last_name')
                ->label('last_name')
                ->state(fn (Delegate $record): ?string => $record->user->last_name),
            ExportColumn::make('phone')
                ->label('phone')
                ->state(fn (Delegate $record): ?string => $record->user->phone),
            ExportColumn::make('birth_date')
                ->label('birth_date')
                ->state(fn (Delegate $record): ?string => $record->user->birth_date?->format('Y-m-d')),
            ExportColumn::make('study_program')
                ->label('study_program')
                ->state(fn (Delegate $record): ?string => $record->user->study_program),
            ExportColumn::make('organization')
                ->label('organization')
                ->state(fn (Delegate $record): string => $record->organization->short_name),
            ExportColumn::make('college')
                ->label('college')
                ->state(fn (Delegate $record): string => $record->seat->collegeOrganization->college->name),
            ExportColumn::make('seat_number')
                ->label('seat_number')
                ->state(fn (Delegate $record): int => $record->seat->seat_number),
            ExportColumn::make('started_at')
                ->label('started_at')
                ->state(fn (Delegate $record): string => $record->started_at->format('Y-m-d')),
            ExportColumn::make('ended_at')
                ->label('ended_at')
                ->state(fn (Delegate $record): ?string => $record->ended_at?->copy()->subSecond()->max($record->started_at)->format('Y-m-d')),
            ExportColumn::make('approval_status')
                ->label('approval_status')
                ->enabledByDefault(false)
                ->state(fn (Delegate $record): string => $record->approval_status->getLabel()),
            ExportColumn::make('approved_at')
                ->label('approved_at')
                ->enabledByDefault(false)
                ->state(fn (Delegate $record): ?string => $record->approved_at?->format('Y-m-d H:i:s')),
        ];
    }

    /**
     * @param  Builder<Delegate>  $query
     * @return Builder<Delegate>
     */
    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['user', 'organization', 'seat.collegeOrganization.college']);
    }

    public static function getCompletedNotificationTitle(Export $export): string
    {
        return __('delegate_export.completed.title');
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = trans_choice('delegate_export.completed.body', $export->successful_rows, ['count' => $export->successful_rows]);

        $failedRowsCount = $export->getFailedRowsCount();

        if ($failedRowsCount > 0) {
            $body .= ' '.trans_choice('delegate_export.completed.failed', $failedRowsCount, ['count' => $failedRowsCount]);
        }

        return $body;
    }

    /**
     * @return array<ExportFormat>
     */
    public function getFormats(): array
    {
        return [ExportFormat::Csv];
    }
}
