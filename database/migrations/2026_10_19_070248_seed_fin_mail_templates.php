<?php

declare(strict_types=1);

use App\Enums\MailTemplateKey;
use FinityLabs\FinMail\Models\EmailTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->templates() as $template) {
            EmailTemplate::query()->updateOrCreate(
                ['key' => $template['key']],
                [...$template, 'is_active' => true, 'is_locked' => true],
            );
        }
    }

    public function down(): void
    {
        // A query-level delete: the model's deleting hook refuses to delete locked templates.
        EmailTemplate::query()
            ->withTrashed()
            ->whereIn('key', array_column(MailTemplateKey::cases(), 'value'))
            ->forceDelete();
    }

    /**
     * @return list<array{key: string, category: string, name: array<string, string>, subject: array<string, string>, body: array<string, string>, token_schema: list<array{token: string, description: string, example: string}>}>
     */
    private function templates(): array
    {
        return [
            $this->approvalRequested(MailTemplateKey::DelegateApprovalRequested, [
                'fr' => ['name' => 'Délégué-e : demande d\'approbation', 'subject' => 'Nouvelle demande d\'approbation de délégué-e', 'intro' => 'Une nouvelle demande de délégué-e requiert votre approbation.', 'detail' => 'Siège : {{ seat }}'],
                'en' => ['name' => 'Delegate: approval request', 'subject' => 'New delegate approval request', 'intro' => 'A new delegate request requires your approval.', 'detail' => 'Seat: {{ seat }}'],
            ], ['token' => 'seat', 'description' => 'Siège demandé', 'example' => 'Siège 1']),
            $this->approvalRequested(MailTemplateKey::VolunteerApprovalRequested, [
                'fr' => ['name' => 'Bénévole : demande d\'approbation', 'subject' => 'Nouvelle demande d\'approbation de mandat de bénévolat', 'intro' => 'Une nouvelle demande de mandat de bénévolat requiert votre approbation.', 'detail' => 'Poste : {{ position }}'],
                'en' => ['name' => 'Volunteer: approval request', 'subject' => 'New volunteer mandate approval request', 'intro' => 'A new volunteer mandate request requires your approval.', 'detail' => 'Position: {{ position }}'],
            ], ['token' => 'position', 'description' => 'Poste demandé', 'example' => 'Trésorier-e']),
            $this->approvalApproved(MailTemplateKey::DelegateApprovalApproved, [
                'fr' => ['name' => 'Délégué-e : demande approuvée', 'subject' => 'Votre demande de délégué-e a été approuvée', 'intro' => 'Nous avons le plaisir de vous informer que votre demande de délégué-e a été approuvée.', 'body' => 'Vous pouvez désormais accéder aux fonctionnalités réservées aux délégué-e-s.'],
                'en' => ['name' => 'Delegate: request approved', 'subject' => 'Your delegate request has been approved', 'intro' => 'We are pleased to inform you that your delegate request has been approved.', 'body' => 'You can now access the features reserved for delegates.'],
            ]),
            $this->approvalApproved(MailTemplateKey::VolunteerApprovalApproved, [
                'fr' => ['name' => 'Bénévole : candidature approuvée', 'subject' => 'Votre candidature au mandat de bénévolat a été approuvée', 'intro' => 'Nous avons le plaisir de vous informer que votre candidature au mandat de bénévolat a été approuvée.', 'body' => 'Vous pouvez désormais accéder aux fonctionnalités réservées aux bénévoles.'],
                'en' => ['name' => 'Volunteer: application approved', 'subject' => 'Your volunteer mandate application has been approved', 'intro' => 'We are pleased to inform you that your volunteer mandate application has been approved.', 'body' => 'You can now access the features reserved for volunteers.'],
            ]),
            $this->proxyApprovalRequested(),
            $this->proxyApproved(),
            $this->magicLink(),
        ];
    }

    /**
     * @param  array<string, array{name: string, subject: string, intro: string, detail: string}>  $texts
     * @param  array{token: string, description: string, example: string}  $detailSchemaEntry
     * @return array{key: string, category: string, name: array<string, string>, subject: array<string, string>, body: array<string, string>, token_schema: list<array{token: string, description: string, example: string}>}
     */
    private function approvalRequested(MailTemplateKey $key, array $texts, array $detailSchemaEntry): array
    {
        return [
            'key' => $key->value,
            'category' => 'notification',
            'name' => array_map(fn (array $text): string => $text['name'], $texts),
            'subject' => array_map(fn (array $text): string => $text['subject'], $texts),
            'body' => [
                'fr' => <<<HTML
                    <p>Bonjour {{ recipient_name }},</p>
                    <p>{$texts['fr']['intro']}</p>
                    <ul>
                        <li>Demandeur : {{ applicant_name }}</li>
                        <li>Association : {{ organization }}</li>
                        <li>{$texts['fr']['detail']}</li>
                        <li>Date de début : {{ started_at }}</li>
                    </ul>
                    {$this->button('Approuver', '{{ approve_url }}')}
                    <p><a href="{{ reject_url }}">Refuser</a></p>
                    <p><a href="{{ details_url }}">Voir les détails</a></p>
                    <p>Merci de traiter cette demande dans les meilleurs délais.</p>
                    HTML,
                'en' => <<<HTML
                    <p>Hello {{ recipient_name }},</p>
                    <p>{$texts['en']['intro']}</p>
                    <ul>
                        <li>Applicant: {{ applicant_name }}</li>
                        <li>Organization: {{ organization }}</li>
                        <li>{$texts['en']['detail']}</li>
                        <li>Start date: {{ started_at }}</li>
                    </ul>
                    {$this->button('Approve', '{{ approve_url }}')}
                    <p><a href="{{ reject_url }}">Reject</a></p>
                    <p><a href="{{ details_url }}">View details</a></p>
                    <p>Please process this request as soon as possible.</p>
                    HTML,
            ],
            'token_schema' => [
                ['token' => 'recipient_name', 'description' => 'Nom de l\'approbateur-ice', 'example' => 'Alice Martin'],
                ['token' => 'applicant_name', 'description' => 'Nom du demandeur ou de la demandeuse', 'example' => 'Jean Dupont'],
                ['token' => 'organization', 'description' => 'Sigle de l\'association', 'example' => 'AFGES'],
                $detailSchemaEntry,
                ['token' => 'started_at', 'description' => 'Date de début du mandat', 'example' => '01/09/2026'],
                ['token' => 'approve_url', 'description' => 'Lien signé pour approuver', 'example' => 'https://example.com/approve'],
                ['token' => 'reject_url', 'description' => 'Lien signé pour refuser', 'example' => 'https://example.com/reject'],
                ['token' => 'details_url', 'description' => 'Lien vers la fiche dans l\'administration', 'example' => 'https://example.com/edit'],
            ],
        ];
    }

    /**
     * @param  array<string, array{name: string, subject: string, intro: string, body: string}>  $texts
     * @return array{key: string, category: string, name: array<string, string>, subject: array<string, string>, body: array<string, string>, token_schema: list<array{token: string, description: string, example: string}>}
     */
    private function approvalApproved(MailTemplateKey $key, array $texts): array
    {
        return [
            'key' => $key->value,
            'category' => 'notification',
            'name' => array_map(fn (array $text): string => $text['name'], $texts),
            'subject' => array_map(fn (array $text): string => $text['subject'], $texts),
            'body' => [
                'fr' => <<<HTML
                    <p>Bonjour {{ recipient_name }},</p>
                    <p>{$texts['fr']['intro']}</p>
                    <p>{$texts['fr']['body']}</p>
                    <p>Merci de votre engagement.</p>
                    HTML,
                'en' => <<<HTML
                    <p>Hello {{ recipient_name }},</p>
                    <p>{$texts['en']['intro']}</p>
                    <p>{$texts['en']['body']}</p>
                    <p>Thank you for your commitment.</p>
                    HTML,
            ],
            'token_schema' => [
                ['token' => 'recipient_name', 'description' => 'Nom du ou de la destinataire', 'example' => 'Jean Dupont'],
            ],
        ];
    }

    /**
     * @return array{key: string, category: string, name: array<string, string>, subject: array<string, string>, body: array<string, string>, token_schema: list<array{token: string, description: string, example: string}>}
     */
    private function proxyApprovalRequested(): array
    {
        return [
            'key' => MailTemplateKey::ProxyApprovalRequested->value,
            'category' => 'notification',
            'name' => ['fr' => 'Procuration : demande d\'approbation', 'en' => 'Proxy: approval request'],
            'subject' => ['fr' => 'Nouvelle procuration à approuver', 'en' => 'New proxy awaiting approval'],
            'body' => [
                'fr' => <<<HTML
                    <p>Une nouvelle procuration a été créée et nécessite votre approbation.</p>
                    <p>Délégant-e : {{ delegator }} → Mandataire : {{ proxy_holder }} pour {{ session }}</p>
                    {$this->button('Voir les procurations', '{{ review_url }}')}
                    HTML,
                'en' => <<<HTML
                    <p>A new proxy has been created and requires your approval.</p>
                    <p>Delegator: {{ delegator }} → Proxy holder: {{ proxy_holder }} for {{ session }}</p>
                    {$this->button('View proxies', '{{ review_url }}')}
                    HTML,
            ],
            'token_schema' => [
                ...$this->proxyTokens(),
                ['token' => 'review_url', 'description' => 'Lien vers la liste des procurations', 'example' => 'https://example.com/proxies'],
            ],
        ];
    }

    /**
     * @return array{key: string, category: string, name: array<string, string>, subject: array<string, string>, body: array<string, string>, token_schema: list<array{token: string, description: string, example: string}>}
     */
    private function proxyApproved(): array
    {
        return [
            'key' => MailTemplateKey::ProxyApproved->value,
            'category' => 'notification',
            'name' => ['fr' => 'Procuration : approuvée', 'en' => 'Proxy: approved'],
            'subject' => ['fr' => 'Votre procuration a été approuvée', 'en' => 'Your proxy has been approved'],
            'body' => [
                'fr' => <<<'HTML'
                    <p>Votre procuration a été approuvée.</p>
                    <p>Délégant-e : {{ delegator }} → Mandataire : {{ proxy_holder }} pour {{ session }}</p>
                    HTML,
                'en' => <<<'HTML'
                    <p>Your proxy has been approved.</p>
                    <p>Delegator: {{ delegator }} → Proxy holder: {{ proxy_holder }} for {{ session }}</p>
                    HTML,
            ],
            'token_schema' => $this->proxyTokens(),
        ];
    }

    /**
     * @return array{key: string, category: string, name: array<string, string>, subject: array<string, string>, body: array<string, string>, token_schema: list<array{token: string, description: string, example: string}>}
     */
    private function magicLink(): array
    {
        return [
            'key' => MailTemplateKey::MagicLink->value,
            'category' => 'system',
            'name' => ['fr' => 'Lien de connexion', 'en' => 'Sign-in link'],
            'subject' => ['fr' => 'Votre lien de connexion', 'en' => 'Your sign-in link'],
            'body' => [
                'fr' => <<<HTML
                    <p>Bonjour !</p>
                    <p>Cliquez sur le bouton ci-dessous pour vous connecter à votre compte.</p>
                    {$this->button('Se connecter', '{{ login_url }}')}
                    <p>Ce lien expirera dans {{ expires_in_minutes }} minutes.</p>
                    <p>Si vous n'avez pas demandé ce lien, ignorez simplement cet e-mail.</p>
                    HTML,
                'en' => <<<HTML
                    <p>Hello!</p>
                    <p>Click the button below to sign in to your account.</p>
                    {$this->button('Sign in', '{{ login_url }}')}
                    <p>This link will expire in {{ expires_in_minutes }} minutes.</p>
                    <p>If you did not request this link, you can safely ignore this email.</p>
                    HTML,
            ],
            'token_schema' => [
                ['token' => 'login_url', 'description' => 'Lien de connexion signé', 'example' => 'https://example.com/login'],
                ['token' => 'expires_in_minutes', 'description' => 'Durée de validité du lien, en minutes', 'example' => '15'],
            ],
        ];
    }

    /**
     * @return list<array{token: string, description: string, example: string}>
     */
    private function proxyTokens(): array
    {
        return [
            ['token' => 'delegator', 'description' => 'Siège du ou de la délégant-e', 'example' => 'Siège 1 (Jean Dupont)'],
            ['token' => 'proxy_holder', 'description' => 'Siège du ou de la mandataire', 'example' => 'Siège 2 (Alice Martin)'],
            ['token' => 'session', 'description' => 'Temps démocratique concerné', 'example' => 'Assemblée générale'],
        ];
    }

    /**
     * The editor's button block, as stored by Fin Mail's rich editor.
     */
    private function button(string $label, string $url): string
    {
        $config = e(json_encode(
            ['label' => $label, 'url' => $url, 'align' => 'center'],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ));

        return "<div data-type=\"customBlock\" data-id=\"emailButton\" data-config=\"{$config}\"></div>";
    }
};
