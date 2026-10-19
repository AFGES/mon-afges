<?php

declare(strict_types=1);

namespace App\Traits;

use App\Enums\MailTemplateKey;
use App\Mail\EscapedTemplateMail;
use Illuminate\Contracts\Translation\HasLocalePreference;

/**
 * Builds the Fin Mail mailable of a notification. Only scalars are passed as tokens, never a model,
 * whose attributes Fin Mail would expose to the template unescaped; EscapedTemplateMail escapes them
 * where the output is HTML.
 */
trait BuildsMailTemplate
{
    /**
     * @param  array<string, string|int|null>  $tokens
     */
    protected function mailTemplate(MailTemplateKey $key, object $notifiable, array $tokens): EscapedTemplateMail
    {
        $locale = $notifiable instanceof HasLocalePreference ? $notifiable->preferredLocale() : null;

        return EscapedTemplateMail::make($key->value, $locale ?? app()->getLocale())
            ->tokens($tokens)
            ->to($notifiable->routeNotificationFor('mail'));
    }
}
