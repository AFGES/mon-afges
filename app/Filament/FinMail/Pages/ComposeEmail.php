<?php

declare(strict_types=1);

namespace App\Filament\FinMail\Pages;

use App\Filament\FinMail\EmailTemplateResource;
use FinityLabs\FinMail\Resources\EmailTemplateResource\Pages\ComposeEmail as FinMailComposeEmail;

/**
 * Fin Mail's free-form compose page sends any content to any address from the association's
 * sender, and only checks that the user may list templates. The application only sends its own
 * notifications, so the page is closed to everyone, the super admin included.
 */
final class ComposeEmail extends FinMailComposeEmail
{
    protected static string $resource = EmailTemplateResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return false;
    }
}
