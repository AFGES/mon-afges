<?php

declare(strict_types=1);

namespace App\Filament\Imports;

use App\Actions\ImportLegacyDelegate;
use App\Models\Delegate;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class DelegateImporter extends Importer
{
    protected static ?string $model = Delegate::class;

    /**
     * @return array<ImportColumn>
     */
    public static function getColumns(): array
    {
        return [
            ImportColumn::make('email')
                ->label(__('delegate_import.columns.email'))
                ->guess(['courriel', 'e-mail', 'mail', 'adresse email'])
                ->example('camille.durand@example.org')
                ->requiredMapping()
                ->rules(['required', 'email']),
            ImportColumn::make('first_name')
                ->label(__('delegate_import.columns.first_name'))
                ->guess(['prenom', 'prénom', 'firstname'])
                ->helperText(__('delegate_import.helpers.names'))
                ->example('Camille')
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('last_name')
                ->label(__('delegate_import.columns.last_name'))
                ->guess(['nom', 'nom de famille', 'lastname', 'surname'])
                ->helperText(__('delegate_import.helpers.names'))
                ->example('Durand')
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('phone')
                ->label(__('delegate_import.columns.phone'))
                ->guess(['telephone', 'téléphone', 'tel', 'mobile'])
                ->example('0601020304')
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('birth_date')
                ->label(__('delegate_import.columns.birth_date'))
                ->guess(['date de naissance', 'naissance', 'birthday', 'birthdate'])
                ->helperText(__('delegate_import.helpers.date'))
                ->example('25/12/2000')
                ->castStateUsing(self::normalizeDate(...))
                ->rules(['nullable', 'date_format:Y-m-d']),
            ImportColumn::make('study_program')
                ->label(__('delegate_import.columns.study_program'))
                ->guess(['cursus', 'formation', 'etudes', 'études', 'filiere', 'filière'])
                ->example('Master 1 Droit')
                ->rules(['nullable', 'max:255']),
            ImportColumn::make('organization')
                ->label(__('delegate_import.columns.organization'))
                ->guess(['association', 'asso', 'organisation', 'nom court', 'short name'])
                ->helperText(__('delegate_import.helpers.organization'))
                ->example('AFGES')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('college')
                ->label(__('delegate_import.columns.college'))
                ->guess(['collège', 'college'])
                ->example('Général')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('seat_number')
                ->label(__('delegate_import.columns.seat_number'))
                ->guess(['siege', 'siège', 'numero de siege', 'numéro de siège', 'seat', 'seat #'])
                ->example('1')
                ->requiredMapping()
                ->numeric(decimalPlaces: 0)
                ->rules(['required', 'integer', 'min:1']),
            ImportColumn::make('started_at')
                ->label(__('delegate_import.columns.started_at'))
                ->guess(['debut', 'début', 'date de debut', 'date de début', 'start', 'start date', 'from'])
                ->helperText(__('delegate_import.helpers.date'))
                ->example('01/09/2025')
                ->requiredMapping()
                ->castStateUsing(self::normalizeDate(...))
                ->rules(['required', 'date_format:Y-m-d']),
            ImportColumn::make('ended_at')
                ->label(__('delegate_import.columns.ended_at'))
                ->guess(['fin', 'date de fin', 'end', 'end date', 'to'])
                ->helperText(__('delegate_import.helpers.date'))
                ->example('31/08/2026')
                ->castStateUsing(self::normalizeDate(...))
                ->rules(['nullable', 'date_format:Y-m-d', 'after_or_equal:started_at']),
        ];
    }

    public static function getCompletedNotificationTitle(Import $import): string
    {
        return __('delegate_import.completed.title');
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = trans_choice('delegate_import.completed.body', $import->successful_rows, ['count' => $import->successful_rows]);

        $failedRowsCount = $import->getFailedRowsCount();

        if ($failedRowsCount > 0) {
            $body .= ' '.trans_choice('delegate_import.completed.failed', $failedRowsCount, ['count' => $failedRowsCount]);
        }

        return $body;
    }

    public function resolveRecord(): Delegate
    {
        return new Delegate;
    }

    /**
     * The row is not mapped attribute by attribute: the delegate is created by the import action in `saveRecord()`.
     */
    public function fillRecord(): void {}

    /**
     * @throws ValidationException When the row cannot be resolved to an approved delegate.
     */
    public function saveRecord(): void
    {
        $this->record = app(ImportLegacyDelegate::class)->handle($this->data, $this->import->user);
    }

    /**
     * Turn a dd/mm/yyyy or yyyy-mm-dd date into yyyy-mm-dd, leaving anything else untouched so validation reports it.
     */
    private static function normalizeDate(?string $state): ?string
    {
        if (blank($state)) {
            return null;
        }

        $state = Str::trim($state);

        foreach (['!d/m/Y', '!Y-m-d'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $state);
            } catch (Throwable) {
                continue;
            }

            if ($date instanceof Carbon && $date->format(Str::after($format, '!')) === $state) {
                return $date->format('Y-m-d');
            }
        }

        return $state;
    }
}
