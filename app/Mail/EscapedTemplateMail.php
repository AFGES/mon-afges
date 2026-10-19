<?php

declare(strict_types=1);

namespace App\Mail;

use FinityLabs\FinMail\Mail\TemplateMail;

/**
 * Fin Mail inserts token values verbatim into the subject, the preheader and the HTML body. The body
 * is HTML, so it gets escaped values; the subject is a plain-text header and the preheader is escaped
 * by the layout, so they get the raw ones. A null value is left to the token's fallback.
 */
final class EscapedTemplateMail extends TemplateMail
{
    /** @var array<string, string|int|null> */
    private array $plainTokens = [];

    /**
     * @param  array<string, string|int|null>  $tokens
     */
    public function tokens(array $tokens): static
    {
        $this->plainTokens = $tokens;
        $this->rendered = [];

        return $this->models(array_map(fn (string|int|null $value): ?string => $value === null ? null : e($value), $tokens));
    }

    /**
     * @return array{subject: string, preheader: string, body: string}
     */
    protected function getRendered(): array
    {
        if ($this->rendered === []) {
            $plain = $this->emailTemplate->render($this->plainTokens);

            $this->rendered = [
                'subject' => $plain['subject'],
                'preheader' => $plain['preheader'],
                'body' => $this->emailTemplate->render($this->models)['body'],
            ];
        }

        return $this->rendered;
    }
}
