<?php

declare(strict_types=1);

use App\Enums\MailTemplateKey;
use FinityLabs\FinMail\Models\EmailTemplate;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function finMailTemplatesMigration(): object
{
    return require database_path('migrations/2026_10_19_070248_seed_fin_mail_templates.php');
}

function finMailPermissionsMigration(): object
{
    return require database_path('migrations/2026_10_19_070247_create_fin_mail_permissions.php');
}

describe('seed_fin_mail_templates', function () {
    it('creates an active, locked template for every key, in French and English', function () {
        $templates = EmailTemplate::query()->get()->keyBy('key');

        expect($templates->keys()->all())->toEqualCanonicalizing(array_column(MailTemplateKey::cases(), 'value'));

        foreach ($templates as $template) {
            expect($template)
                ->is_active->toBeTrue()
                ->is_locked->toBeTrue()
                ->and(array_keys($template->getTranslations('subject')))->toEqualCanonicalizing(['fr', 'en'])
                ->and(array_keys($template->getTranslations('body')))->toEqualCanonicalizing(['fr', 'en']);
        }
    });

    it('documents every token the body uses in the token schema', function () {
        foreach (EmailTemplate::query()->get() as $template) {
            preg_match_all('/\{\{\s*(\w+)\s*\}\}/', (string) $template->getTranslation('body', 'fr'), $matches);

            expect(array_column($template->token_schema, 'token'))->toEqualCanonicalizing(array_unique($matches[1]));
        }
    });

    it('removes the templates when rolled back', function () {
        finMailTemplatesMigration()->down();

        expect(EmailTemplate::query()->withTrashed()->exists())->toBeFalse();
    });
});

describe('create_fin_mail_permissions', function () {
    it('creates the permissions of the Fin Mail policies and settings pages without assigning them', function () {
        Role::findOrCreate('super_admin', 'web');

        finMailPermissionsMigration()->up();

        expect(Permission::query()->where('guard_name', 'web')->pluck('name')->all())->toContain(
            'ViewAny:EmailTemplate', 'View:EmailTemplate', 'Update:EmailTemplate', 'Preview:EmailTemplate', 'SendTest:EmailTemplate',
            'ViewAny:SentEmail', 'View:SentEmail', 'Resend:SentEmail',
            'View:ManageGeneralSettings', 'View:ManageBrandingSettings', 'View:ManageLoggingSettings',
            'View:ManageAttachmentSettings', 'View:ManageAuthEmailSettings',
        )->and(DB::table(config('permission.table_names.role_has_permissions'))->count())->toBe(0);
    });

    it('removes the permissions when rolled back', function () {
        finMailPermissionsMigration()->down();

        expect(Permission::query()->where('name', 'like', '%:EmailTemplate')->exists())->toBeFalse()
            ->and(Permission::query()->where('name', 'like', 'View:Manage%Settings')->exists())->toBeFalse();
    });
});
