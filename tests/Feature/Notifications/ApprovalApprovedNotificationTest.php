<?php

declare(strict_types=1);

use App\Enums\MailTemplateKey;
use App\Models\Delegate;
use App\Models\User;
use App\Models\Volunteer;
use App\Notifications\ApprovalApprovedNotification;
use FinityLabs\FinMail\Models\SentEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    app()->setLocale('fr');
});

it('is sent to the mandate owner when the mandate is approved', function (string $model) {
    $mandate = $model::factory()->create();
    $approver = User::factory()->create();
    Notification::fake([ApprovalApprovedNotification::class]);

    $this->actingAs($approver);
    $mandate->approve();

    Notification::assertSentTo(
        $mandate->user,
        ApprovalApprovedNotification::class,
        fn (ApprovalApprovedNotification $notification): bool => $notification->approvable->is($mandate),
    );
})->with([
    'delegate' => Delegate::class,
    'volunteer' => Volunteer::class,
]);

it('is not sent again when the mandate is already approved', function (string $model) {
    $mandate = $model::factory()->approved()->create();
    Notification::fake([ApprovalApprovedNotification::class]);

    $mandate->approve();

    Notification::assertNothingSent();
})->with([
    'delegate' => Delegate::class,
    'volunteer' => Volunteer::class,
]);

it('is sent by mail and queued', function () {
    $notification = new ApprovalApprovedNotification(Delegate::factory()->make());

    expect($notification->via(new User))->toBe(['mail'])
        ->and($notification)->toBeInstanceOf(ShouldQueue::class);
});

it('renders the template of the approved mandate type for the mandate owner', function (string $model, MailTemplateKey $key, string $subject) {
    $recipient = User::factory()->make(['first_name' => 'Test', 'last_name' => 'User', 'email' => 'owner@afges.org']);

    $mail = (new ApprovalApprovedNotification($model::factory()->make()))->toMail($recipient);

    expect($mail->getTemplate()->key)->toBe($key->value)
        ->and($mail->hasTo('owner@afges.org'))->toBeTrue()
        ->and($mail->envelope()->subject)->toBe($subject)
        ->and($mail->render())->toContain('Bonjour Test User,');
})->with([
    'delegate' => [Delegate::class, MailTemplateKey::DelegateApprovalApproved, 'Votre demande de délégué-e a été approuvée'],
    'volunteer' => [Volunteer::class, MailTemplateKey::VolunteerApprovalApproved, 'Votre candidature au mandat de bénévolat a été approuvée'],
]);

it('identifies the approved record in its array form', function () {
    $delegate = Delegate::factory()->create();

    $array = (new ApprovalApprovedNotification($delegate))->toArray(new User);

    expect($array)->toBe([
        'approvable_type' => Delegate::class,
        'approvable_id' => $delegate->id,
    ]);
});

it('escapes the recipient name in the rendered mail', function () {
    $recipient = User::factory()->make(['first_name' => "<script>alert('xss')</script>", 'last_name' => 'Doe']);

    $content = (new ApprovalApprovedNotification(Delegate::factory()->make()))->toMail($recipient)->render();

    expect($content)
        ->toContain('&lt;script&gt;')
        ->not->toContain("<script>alert('xss')</script>");
});

it('stores the rendered body in the sent-emails log', function () {
    $recipient = User::factory()->create();

    $recipient->notify(new ApprovalApprovedNotification(Delegate::factory()->create()));

    expect(SentEmail::query()->sole()->rendered_body)->toContain($recipient->name);
});
