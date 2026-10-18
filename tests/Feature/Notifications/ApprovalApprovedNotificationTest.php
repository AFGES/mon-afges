<?php

declare(strict_types=1);

use App\Models\Delegate;
use App\Models\User;
use App\Models\Volunteer;
use App\Notifications\ApprovalApprovedNotification;
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

it('uses the subject of the approved mandate type', function (string $model, string $subject) {
    $notification = new ApprovalApprovedNotification($model::factory()->make());

    $mail = $notification->toMail(User::factory()->make(['first_name' => 'Test', 'last_name' => 'User']));

    expect($mail->subject)->toBe($subject)
        ->and($mail->greeting)->toBe('Bonjour Test User,');
})->with([
    'delegate' => [Delegate::class, 'Votre demande de délégué-e a été approuvée'],
    'volunteer' => [Volunteer::class, 'Votre candidature au mandat de bénévolat a été approuvée'],
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

    expect((string) $content)
        ->toContain('&lt;script&gt;')
        ->not->toContain("<script>alert('xss')</script>");
});

it('does not turn a Markdown link in the recipient name into a link', function () {
    $recipient = User::factory()->make(['first_name' => '[click](https://evil.example)', 'last_name' => 'Doe']);

    $content = (new ApprovalApprovedNotification(Delegate::factory()->make()))->toMail($recipient)->render();

    expect((string) $content)->not->toContain('<a href="https://evil.example"');
});
