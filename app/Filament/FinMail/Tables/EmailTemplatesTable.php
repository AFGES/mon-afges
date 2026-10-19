<?php

declare(strict_types=1);

namespace App\Filament\FinMail\Tables;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Tables\Table;
use FinityLabs\FinMail\Models\EmailTemplate;
use FinityLabs\FinMail\Resources\EmailTemplateResource\Tables\EmailTemplatesTable as FinMailEmailTemplatesTable;
use FinityLabs\FinMail\Settings\GeneralSettings;

/**
 * Fin Mail's template table, with the two actions that send free content locked down:
 * - compose is hidden: its page is closed (see ComposeEmail);
 * - send test only goes to the signed-in user, as it renders an editable template with token values
 *   typed in the form and inserted unescaped.
 */
final class EmailTemplatesTable extends FinMailEmailTemplatesTable
{
    public static function configure(Table $table): Table
    {
        $table = parent::configure($table);

        $table->getAction('compose')?->hidden();

        $table->getAction('send_test')
            ?->schema(self::sendTestSchema(...))
            ->mutateDataUsing(fn (array $data): array => [...$data, 'test_email' => self::signedInUser()->email]);

        return $table;
    }

    /**
     * @return list<TextInput|Select|Section>
     */
    private static function sendTestSchema(EmailTemplate $record): array
    {
        $tokenFields = self::buildTokenFields(self::tokenSchema($record));

        return [
            TextInput::make('test_email')
                ->label(__('fin-mail::fin-mail.template.actions.send_test_field'))
                ->default(fn (): string => self::signedInUser()->email)
                ->disabled(),

            Select::make('locale')
                ->label(__('fin-mail::fin-mail.template.actions.send_test_locale'))
                ->options(fn (): array => collect(app(GeneralSettings::class)->languages)->pluck('display', 'code')->all())
                ->default(fn (): string => app()->getLocale())
                ->native(false)
                ->required(),

            ...($tokenFields === [] ? [] : [
                Section::make(__('fin-mail::fin-mail.template.tokens.label'))->schema($tokenFields),
            ]),
        ];
    }

    /**
     * @return list<array{token: string, description?: string, example?: string}>
     */
    private static function tokenSchema(EmailTemplate $record): array
    {
        $tokenSchema = [];

        foreach ($record->token_schema ?? [] as $token) {
            if (is_array($token) && is_string($token['token'] ?? null)) {
                $tokenSchema[] = array_filter([
                    'token' => $token['token'],
                    'description' => is_string($token['description'] ?? null) ? $token['description'] : null,
                    'example' => is_string($token['example'] ?? null) ? $token['example'] : null,
                ], fn (?string $value): bool => $value !== null);
            }
        }

        return $tokenSchema;
    }

    private static function signedInUser(): User
    {
        /** @var User */
        return auth()->user();
    }
}
