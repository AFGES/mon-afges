<?php

declare(strict_types=1);

use App\Enums\MailTemplateKey;
use App\Models\User;
use App\Traits\BuildsMailTemplate;
use FinityLabs\FinMail\Mail\TemplateMail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Notifications\Notifiable;

beforeEach(function () {
    app()->setLocale('fr');
});

/**
 * @param  array<string, string|int>  $tokens
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
