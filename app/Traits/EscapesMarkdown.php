<?php

declare(strict_types=1);

namespace App\Traits;

trait EscapesMarkdown
{
    /**
     * Backslash-escape characters that would let user input create Markdown links or images; `<` is left alone because the mail renderer already HTML-escapes lines.
     */
    protected function escapeMarkdown(string $value): string
    {
        return strtr($value, [
            '\\' => '\\\\',
            '[' => '\\[',
            ']' => '\\]',
            '!' => '\\!',
            '`' => '\\`',
        ]);
    }
}
