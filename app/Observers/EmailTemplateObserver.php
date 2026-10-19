<?php

declare(strict_types=1);

namespace App\Observers;

use FinityLabs\FinMail\Models\EmailTemplate;

final class EmailTemplateObserver
{
    /**
     * The application sends locked templates by key, and Fin Mail only finds active ones: a locked
     * template switched off would make every send of it throw. They stay active, whatever the form says.
     */
    public function saving(EmailTemplate $emailTemplate): void
    {
        if ($emailTemplate->is_locked) {
            $emailTemplate->is_active = true;
        }
    }
}
