<?php

declare(strict_types=1);

use App\Enums\MailTemplateKey;
use App\Models\User;
use App\Traits\BuildsMailTemplate;
use FinityLabs\FinMail\Mail\TemplateMail;
use FinityLabs\FinMail\Models\EmailTemplate;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Notifications\Notifiable;

beforeEach(function () {
    app()->setLocale('fr');
});

/**
 * @param  array<string, string|int|null>  $tokens
 */
function buildMailTemplate(MailTemplateKey $key, object $notifiable, array $tokens): TemplateMail
{
    $builder = new class
    {
        use BuildsMailTemplate {
            mailTemplate as public;
        }
    };

    return $builder->mailTemplate($key, $notifiable, $tokens);
}

describe('mailTemplate', function () {
    it('escapes the token values in the rendered body', function () {
        $recipient = User::factory()->make();

        $content = buildMailTemplate(MailTemplateKey::DelegateApprovalApproved, $recipient, [
            'recipient_name' => '<a href=x>Jean</a>',
        ])->render();

        expect($content)
            ->toContain('&lt;a href=x&gt;Jean&lt;/a&gt;')
            ->not->toContain('<a href=x>');
    });

    it('keeps the token values raw in the plain-text subject and preheader', function () {
        $template = EmailTemplate::query()->where('key', MailTemplateKey::DelegateApprovalApproved->value)->sole();
        $template->setTranslation('subject', 'fr', 'Bienvenue {{ recipient_name }}')
            ->setTranslation('preheader', 'fr', 'Pour {{ recipient_name }}')
            ->save();

        $mail = buildMailTemplate(MailTemplateKey::DelegateApprovalApproved, User::factory()->make(), [
            'recipient_name' => "Jeanne D'Arc & Cie",
        ]);

        expect($mail->envelope()->subject)->toBe("Bienvenue Jeanne D'Arc & Cie")
            ->and($mail->render())->toContain('Pour Jeanne D&#039;Arc &amp; Cie')
            ->not->toContain('&amp;#039;');
    });

    it('leaves a null token value to the fallback of the template', function () {
        EmailTemplate::query()->where('key', MailTemplateKey::DelegateApprovalApproved->value)->sole()
            ->setTranslation('body', 'fr', '<p>Bonjour {{ recipient_name | \'cher membre\' }}</p>')
            ->save();

        $content = buildMailTemplate(MailTemplateKey::DelegateApprovalApproved, User::factory()->make(), [
            'recipient_name' => null,
        ])->render();

        expect($content)->toContain('Bonjour cher membre');
    });

    it('addresses the mail to the mail route of the notifiable', function () {
        $recipient = User::factory()->make(['email' => 'jean@afges.org']);

        $mail = buildMailTemplate(MailTemplateKey::MagicLink, $recipient, []);

        expect($mail->hasTo('jean@afges.org'))->toBeTrue();
    });

    it('renders the template in the locale the notifiable prefers', function () {
        $recipient = new class implements HasLocalePreference
        {
            use Notifiable;

            public string $email = 'jean@afges.org';

            public function preferredLocale(): string
            {
                return 'en';
            }
        };

        $mail = buildMailTemplate(MailTemplateKey::MagicLink, $recipient, ['expires_in_minutes' => 15]);

        expect($mail->envelope()->subject)->toBe('Your sign-in link')
            ->and($mail->render())->toContain('This link will expire in 15 minutes.');
    });

    it('falls back to the application locale', function () {
        $mail = buildMailTemplate(MailTemplateKey::MagicLink, User::factory()->make(), []);

        expect($mail->envelope()->subject)->toBe('Votre lien de connexion');
    });
});
