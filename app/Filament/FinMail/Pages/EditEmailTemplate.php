<?php

declare(strict_types=1);

namespace App\Filament\FinMail\Pages;

use App\Filament\FinMail\EmailTemplateResource;
use Filament\Actions\Action;
use FinityLabs\FinMail\Resources\EmailTemplateResource\Pages\EditEmailTemplate as FinMailEditEmailTemplate;

/**
 * Fin Mail's edit page without its compose action: the compose page is closed (see ComposeEmail),
 * and the action only checks a permission that the super admin always passes.
 */
final class EditEmailTemplate extends FinMailEditEmailTemplate
{
    protected static string $resource = EmailTemplateResource::class;

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return array_values(array_filter(
            parent::getHeaderActions(),
            fn (Action $action): bool => $action->getName() !== 'compose',
        ));
    }
}
