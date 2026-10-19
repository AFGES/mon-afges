<?php

declare(strict_types=1);

namespace App\Filament\FinMail\Pages;

use App\Filament\FinMail\EmailTemplateResource;
use FinityLabs\FinMail\Resources\EmailTemplateResource\Pages\ListEmailTemplates as FinMailListEmailTemplates;

/**
 * Fin Mail's list page bound to the application's resource, so the table gets its restricted actions.
 */
final class ListEmailTemplates extends FinMailListEmailTemplates
{
    protected static string $resource = EmailTemplateResource::class;
}
