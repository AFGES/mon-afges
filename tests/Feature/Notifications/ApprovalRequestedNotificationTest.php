<?php

declare(strict_types=1);

use App\Enums\MailTemplateKey;
use App\Filament\Clusters\DemocraticTimes\Resources\Delegates\DelegateResource;
use App\Filament\Resources\Volunteers\VolunteerResource;
use App\Models\Delegate;
use App\Models\User;
use App\Models\Volunteer;
use App\Notifications\ApprovalRequestedNotification;
use FinityLabs\FinMail\Models\SentEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

beforeEach(function () {
    app()->setLocale('fr');
});

/**
 * The signed links of the mail, keyed by the route they target.
 *
 * @return array{approve: string, reject: string}
 */
function approvalLinks(Delegate|Volunteer $approvable): array
{
    $routePrefix = Str::plural(Str::snake(class_basename($approvable)));
    $links = templateMailLinks((new ApprovalRequestedNotification($approvable))->toMail(User::factory()->make()));
    $linkTo = fn (string $action): string => collect($links)
        ->first(fn (string $link): bool => Str::startsWith($link, route("{$routePrefix}.{$action}", $approvable)));

    return ['approve' => $linkTo('approve'), 'reject' => $linkTo('reject')];
}

it('is sent by mail and queued', function (string $model) {
    $notification = new ApprovalRequestedNotification($model::factory()->make());

    expect($notification->via(new User))->toBe(['mail'])
        ->and($notification)->toBeInstanceOf(ShouldQueue::class);
})->with([Delegate::class, Volunteer::class]);

it('offers signed approve and reject links that expire after the configured number of days', function (string $model) {
    $this->freezeTime();

    foreach (approvalLinks($model::factory()->create()) as $link) {
        parse_str((string) parse_url($link, PHP_URL_QUERY), $query);

        expect(URL::hasValidSignature(Request::create($link)))->toBeTrue()
            ->and((int) $query['expires'])->toBe(now()->addDays(7)->getTimestamp());
    }
})->with([Delegate::class, Volunteer::class]);

it('does not store the rendered body, which holds the signed links, in the sent-emails log', function () {
    $approver = User::factory()->create();

    $approver->notify(new ApprovalRequestedNotification(Delegate::factory()->create()));

    expect(SentEmail::query()->sole())
        ->rendered_body->toBeNull()
        ->to->toBe([$approver->email]);
});

describe('for a delegate', function () {
    it('renders the delegate template for the approver', function () {
        $delegate = Delegate::factory()->create();
        $approver = User::factory()->make(['first_name' => 'Alice', 'last_name' => 'Approver', 'email' => 'approver@afges.org']);

        $mail = (new ApprovalRequestedNotification($delegate))->toMail($approver);

        expect($mail->getTemplate()->key)->toBe(MailTemplateKey::DelegateApprovalRequested->value)
            ->and($mail->hasTo('approver@afges.org'))->toBeTrue()
            ->and($mail->envelope()->subject)->toBe("Nouvelle demande d'approbation de délégué-e")
            ->and($mail->render())
            ->toContain('Bonjour Alice Approver,')
            ->toContain('Demandeur : '.e($delegate->user->name))
            ->toContain('Association : '.e($delegate->organization->short_name))
            ->toContain('Siège : '.e("{$delegate->seat->collegeOrganization->college->name} #{$delegate->seat->seat_number}"))
            ->not->toContain(e($delegate->seat->label()))
            ->and(templateMailLinks($mail))->toContain(DelegateResource::getUrl('edit', ['record' => $delegate]));
    });

    it('escapes the applicant name in the rendered mail', function () {
        $applicant = User::factory()->create(['first_name' => "<script>alert('xss')</script>", 'last_name' => 'Doe']);
        $delegate = Delegate::factory()->for($applicant)->create();

        $content = (new ApprovalRequestedNotification($delegate))->toMail(User::factory()->make())->render();

        expect($content)
            ->toContain('&lt;script&gt;')
            ->not->toContain("<script>alert('xss')</script>");
    });
});

describe('for a volunteer', function () {
    it('renders the volunteer template for the approver', function () {
        $volunteer = Volunteer::factory()->create();
        $approver = User::factory()->make(['first_name' => 'Alice', 'last_name' => 'Approver']);

        $mail = (new ApprovalRequestedNotification($volunteer))->toMail($approver);

        expect($mail->getTemplate()->key)->toBe(MailTemplateKey::VolunteerApprovalRequested->value)
            ->and($mail->envelope()->subject)->toBe("Nouvelle demande d'approbation de mandat de bénévolat")
            ->and($mail->render())
            ->toContain('Bonjour Alice Approver,')
            ->toContain('Poste : '.e($volunteer->position_title))
            ->and(templateMailLinks($mail))->toContain(VolunteerResource::getUrl('edit', ['record' => $volunteer]));
    });

    it('escapes the applicant name and position in the rendered mail', function () {
        $applicant = User::factory()->create(['first_name' => "<script>alert('xss')</script>", 'last_name' => 'Doe']);
        $volunteer = Volunteer::factory()->for($applicant)->create(['position_title' => '<img src=x onerror=alert(1)>']);

        $content = (new ApprovalRequestedNotification($volunteer))->toMail(User::factory()->make())->render();

        expect($content)
            ->toContain('&lt;script&gt;')
            ->not->toContain("<script>alert('xss')</script>")
            ->not->toContain('<img src=x');
    });
});
