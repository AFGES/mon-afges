<?php

declare(strict_types=1);

use App\Enums\MailTemplateKey;
use App\Filament\FinMail\Pages\ComposeEmail;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Testing\TestAction;
use FinityLabs\FinMail\Clusters\FinMailSettings\Pages\ManageAttachmentSettings;
use FinityLabs\FinMail\Clusters\FinMailSettings\Pages\ManageAuthEmailSettings;
use FinityLabs\FinMail\Clusters\FinMailSettings\Pages\ManageBrandingSettings;
use FinityLabs\FinMail\Clusters\FinMailSettings\Pages\ManageGeneralSettings;
use FinityLabs\FinMail\Clusters\FinMailSettings\Pages\ManageLoggingSettings;
use FinityLabs\FinMail\Models\EmailTemplate;
use FinityLabs\FinMail\Resources\EmailTemplateResource\EmailTemplateResource;
use FinityLabs\FinMail\Resources\EmailTemplateResource\Pages\EditEmailTemplate;
use FinityLabs\FinMail\Resources\EmailTemplateResource\Pages\ListEmailTemplates;
use FinityLabs\FinMail\Resources\SentEmailResource\Pages\ListSentEmails;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function () {
    app()->setLocale('fr');
});

function magicLinkTemplate(): EmailTemplate
{
    return EmailTemplate::query()->where('key', MailTemplateKey::MagicLink->value)->sole();
}

function actingAsSuperAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate(User::SUPER_ADMIN_ROLE, 'web'));
    actingAs($user);

    return $user;
}

describe('a user without permissions', function () {
    beforeEach(function () {
        actingAs(User::factory()->create());
    });

    it('is forbidden from the template and sent-email lists', function (string $listPage) {
        livewire($listPage)->assertForbidden();
    })->with([
        'templates' => ListEmailTemplates::class,
        'sent emails' => ListSentEmails::class,
    ]);

    it('is forbidden from editing a template', function () {
        livewire(EditEmailTemplate::class, ['record' => magicLinkTemplate()->getRouteKey()])->assertForbidden();
    });

    it('is forbidden from every settings page', function (string $settingsPage) {
        livewire($settingsPage)->assertForbidden();
    })->with([
        ManageGeneralSettings::class,
        ManageBrandingSettings::class,
        ManageLoggingSettings::class,
        ManageAttachmentSettings::class,
        ManageAuthEmailSettings::class,
    ]);
});

it('forbids everyone from composing a free-form email, template editors and super admins included', function (Closure $actingAs) {
    $actingAs();

    livewire(ComposeEmail::class, ['record' => magicLinkTemplate()])->assertForbidden();
})->with([
    'user without permissions' => fn (): Closure => fn () => actingAs(User::factory()->create()),
    'template editor' => fn (): Closure => fn () => actingAs(User::factory()->create()->givePermissionTo(
        Permission::findOrCreate('ViewAny:EmailTemplate', 'web'),
        Permission::findOrCreate('View:EmailTemplate', 'web'),
        Permission::findOrCreate('Update:EmailTemplate', 'web'),
    )),
    'super admin' => fn (): Closure => actingAsSuperAdmin(...),
]);

describe('a super admin', function () {
    beforeEach(function () {
        actingAsSuperAdmin();
    });

    it('edits the content of a template', function () {
        livewire(EditEmailTemplate::class, ['record' => magicLinkTemplate()->getRouteKey()])
            ->fillForm(['subject' => 'Connectez-vous'])
            ->call('save')
            ->assertHasNoFormErrors();

        expect(magicLinkTemplate()->getTranslation('subject', 'fr'))->toBe('Connectez-vous')
            ->and(magicLinkTemplate()->getTranslation('subject', 'en'))->toBe('Your sign-in link');
    });

    it('cannot create or delete templates', function () {
        $template = magicLinkTemplate();

        expect(EmailTemplateResource::canCreate())->toBeFalse()
            ->and(EmailTemplateResource::canDelete($template))->toBeFalse();

        livewire(ListEmailTemplates::class)
            ->assertActionHidden(TestAction::make(DeleteAction::class)->table($template))
            ->selectTableRecords([$template])
            ->assertActionHidden(TestAction::make(DeleteBulkAction::class)->table()->bulk());

        expect($template->delete())->toBeFalse()
            ->and(magicLinkTemplate()->trashed())->toBeFalse();
    });
});
