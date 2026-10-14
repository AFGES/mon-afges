<?php

declare(strict_types=1);

use App\Filament\Resources\Volunteers\VolunteerResource;
use App\Models\User;
use App\Models\Volunteer;
use App\Notifications\VolunteerApprovalRequestedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    app()->setLocale('fr');
});

it('is sent by mail and queued', function () {
    $notification = new VolunteerApprovalRequestedNotification(Volunteer::factory()->make());

    expect($notification->via(new User))->toBe(['mail'])
        ->and($notification)->toBeInstanceOf(ShouldQueue::class);
});

it('describes the applicant, the organization and the position', function () {
    $volunteer = Volunteer::factory()->create();
    $approver = User::factory()->make(['first_name' => 'Alice', 'last_name' => 'Approver']);

    $mail = (new VolunteerApprovalRequestedNotification($volunteer))->toMail($approver);

    expect($mail->subject)->toBe("Nouvelle demande d'approbation de mandat de bénévolat")
        ->and($mail->greeting)->toBe('Bonjour Alice Approver,')
        ->and(collect($mail->introLines)->implode("\n"))
        ->toContain("Demandeur : {$volunteer->user->name}")
        ->toContain("Association : {$volunteer->organization->short_name}")
        ->toContain("Poste : {$volunteer->position_title}");
});

it('links to the signed approve url', function () {
    $volunteer = Volunteer::factory()->create();

    $mail = (new VolunteerApprovalRequestedNotification($volunteer))->toMail(User::factory()->make());

    expect(URL::hasValidSignature(Request::create($mail->actionUrl)))->toBeTrue()
        ->and($mail->actionUrl)->toContain(route('volunteers.approve', ['volunteer' => $volunteer->id], false));
});

it('offers a signed reject link and a link to the edit page', function () {
    $volunteer = Volunteer::factory()->create();

    $mail = (new VolunteerApprovalRequestedNotification($volunteer))->toMail(User::factory()->make());

    expect(collect($mail->outroLines)->implode("\n"))
        ->toContain(route('volunteers.reject', ['volunteer' => $volunteer->id], false))
        ->toContain(VolunteerResource::getUrl('edit', ['record' => $volunteer]));
});

it('escapes the applicant name and position in the rendered mail', function () {
    $applicant = User::factory()->create(['first_name' => "<script>alert('xss')</script>", 'last_name' => 'Doe']);
    $volunteer = Volunteer::factory()->for($applicant)->create(['position_title' => '<img src=x onerror=alert(1)>']);

    $content = (string) (new VolunteerApprovalRequestedNotification($volunteer))->toMail(User::factory()->make())->render();

    expect($content)
        ->toContain('&lt;script&gt;')
        ->not->toContain("<script>alert('xss')</script>")
        ->not->toContain('<img src=x');
});

it('expires the approve and reject links after the configured number of days', function () {
    $this->freezeTime();
    $volunteer = Volunteer::factory()->create();

    $mail = (new VolunteerApprovalRequestedNotification($volunteer))->toMail(User::factory()->make());

    parse_str((string) parse_url($mail->actionUrl, PHP_URL_QUERY), $query);
    expect((int) $query['expires'])->toBe(now()->addDays(7)->getTimestamp())
        ->and(URL::hasValidSignature(Request::create($mail->actionUrl)))->toBeTrue();
});
