<?php

declare(strict_types=1);

use App\Filament\Clusters\DemocraticTimes\Resources\Delegates\DelegateResource;
use App\Models\Delegate;
use App\Models\User;
use App\Notifications\DelegateApprovalRequestedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    app()->setLocale('fr');
});

it('is sent by mail and queued', function () {
    $notification = new DelegateApprovalRequestedNotification(Delegate::factory()->make());

    expect($notification->via(new User))->toBe(['mail'])
        ->and($notification)->toBeInstanceOf(ShouldQueue::class);
});

it('describes the applicant and the organization', function () {
    $delegate = Delegate::factory()->create();
    $approver = User::factory()->make(['name' => 'Alice Approver']);

    $mail = (new DelegateApprovalRequestedNotification($delegate))->toMail($approver);

    expect($mail->subject)->toBe("Nouvelle demande d'approbation de délégué-e")
        ->and($mail->greeting)->toBe('Bonjour Alice Approver,')
        ->and(collect($mail->introLines)->implode("\n"))
        ->toContain("Demandeur : {$delegate->user->name}")
        ->toContain("Association : {$delegate->organization->short_name}");
});

it('links to the signed approve url', function () {
    $delegate = Delegate::factory()->create();

    $mail = (new DelegateApprovalRequestedNotification($delegate))->toMail(User::factory()->make());

    expect(URL::hasValidSignature(Request::create($mail->actionUrl)))->toBeTrue()
        ->and($mail->actionUrl)->toContain(route('delegates.approve', ['delegate' => $delegate->id], false));
});

it('offers a signed reject link and a link to the edit page', function () {
    $delegate = Delegate::factory()->create();

    $mail = (new DelegateApprovalRequestedNotification($delegate))->toMail(User::factory()->make());

    expect(collect($mail->outroLines)->implode("\n"))
        ->toContain(route('delegates.reject', ['delegate' => $delegate->id], false))
        ->toContain(DelegateResource::getUrl('edit', ['record' => $delegate]));
});

it('escapes the applicant name in the rendered mail', function () {
    $applicant = User::factory()->create(['name' => "<script>alert('xss')</script>"]);
    $delegate = Delegate::factory()->for($applicant)->create();

    $content = (new DelegateApprovalRequestedNotification($delegate))->toMail(User::factory()->make())->render();

    expect((string) $content)
        ->toContain('&lt;script&gt;')
        ->not->toContain("<script>alert('xss')</script>");
});
