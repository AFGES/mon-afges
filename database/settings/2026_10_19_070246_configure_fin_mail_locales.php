<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->update('fin-mail.default_locale', fn (): string => 'fr');
        $this->migrator->update('fin-mail.languages', fn (): array => [
            ['code' => 'fr', 'display' => 'Français', 'flag-icon' => 'fr'],
            ['code' => 'en', 'display' => 'English', 'flag-icon' => 'gb'],
        ]);
    }

    public function down(): void
    {
        $this->migrator->update('fin-mail.default_locale', fn (): string => 'en');
        $this->migrator->update('fin-mail.languages', fn (): array => [
            ['code' => 'en', 'display' => 'English', 'flag-icon' => 'gb'],
        ]);
    }
};
