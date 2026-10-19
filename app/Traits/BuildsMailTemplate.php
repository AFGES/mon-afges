<?php

declare(strict_types=1);

namespace App\Traits;

use App\Enums\MailTemplateKey;
use FinityLabs\FinMail\Mail\TemplateMail;
use Illuminate\Contracts\Translation\HasLocalePreference;

/**
 * Builds the Fin Mail mailable of a notification. Fin Mail inserts token values into the HTML body
 * verbatim, so every value is escaped here and only scalars are passed: never hand it a model,
 * whose attributes it would expose to the template unescaped.
 */
trait BuildsMailTemplate
{
    /**
     * @param  array<string, string|int>  $tokens
     */
    protected function mailTemplate(MailTemplateKey $key, object $notifiable, array $tokens): TemplateMail
    {
        $locale = $notifiable instanceof HasLocalePreference ? $notifiable->preferredLocale() : null;

        return TemplateMail::make($key->value, $locale ?? app()->getLocale())
            ->models(array_map(fn (string|int $value): string => e($value), $tokens))
            ->to($notifiable->routeNotificationFor('mail'));
    }
}
