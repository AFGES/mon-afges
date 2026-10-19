<?php

declare(strict_types=1);

namespace App\Filament\FinMail;

use App\Filament\FinMail\Pages\ComposeEmail;
use App\Filament\FinMail\Pages\EditEmailTemplate;
use App\Filament\FinMail\Pages\ListEmailTemplates;
use App\Filament\FinMail\Tables\EmailTemplatesTable;
use Filament\Tables\Table;
use FinityLabs\FinMail\Resources\EmailTemplateResource\EmailTemplateResource as FinMailEmailTemplateResource;

/**
 * Fin Mail's template resource with its compose page locked and its free-sending actions restricted.
 * Registered through FinMailPlugin::emailTemplateResource(), outside app/Filament/Resources so the
 * panel does not discover it a second time.
 */
final class EmailTemplateResource extends FinMailEmailTemplateResource
{
    public static function table(Table $table): Table
    {
        return EmailTemplatesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            ...parent::getPages(),
            'index' => ListEmailTemplates::route('/'),
            'edit' => EditEmailTemplate::route('/{record}/edit'),
            'compose' => ComposeEmail::route('/{record}/compose'),
        ];
    }
}
