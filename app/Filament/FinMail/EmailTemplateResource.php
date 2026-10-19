<?php

declare(strict_types=1);

namespace App\Filament\FinMail;

use App\Filament\FinMail\Pages\ComposeEmail;
use FinityLabs\FinMail\Resources\EmailTemplateResource\EmailTemplateResource as FinMailEmailTemplateResource;

/**
 * Fin Mail's template resource with its compose page locked. Registered through
 * FinMailPlugin::emailTemplateResource(), outside app/Filament/Resources so the panel does not
 * discover it a second time.
 */
final class EmailTemplateResource extends FinMailEmailTemplateResource
{
    public static function getPages(): array
    {
        return [
            ...parent::getPages(),
            'compose' => ComposeEmail::route('/{record}/compose'),
        ];
    }
}
