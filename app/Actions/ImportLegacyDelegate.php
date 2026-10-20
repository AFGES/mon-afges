<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\College;
use App\Models\CollegeOrganization;
use App\Models\Delegate;
use App\Models\Organization;
use App\Models\Scopes\ApprovedScope;
use App\Models\Scopes\CurrentlyScope;
use App\Models\Seat;
use App\Models\User;
use App\Services\DelegateApprovalService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ImportLegacyDelegate
{
    public const array USER_FIELDS = [
        'first_name',
        'last_name',
        'phone',
        'birth_date',
        'study_program',
    ];

    public function __construct(private DelegateApprovalService $approvalService) {}

    /**
     * Lowercase, accent-free and single-spaced version of a label, used to compare names typed by hand.
     */
    public static function normalizeLabel(string $label): string
    {
        return Str::of($label)->ascii()->lower()->squish()->toString();
    }

    /**
     * Import one legacy delegate row: find or create the user, resolve the seat and create the approved delegate.
     *
     * The whole row runs in a transaction, so a created user is rolled back when the delegate cannot be created.
     * A delegate already imported for the same user, seat and start date is returned untouched.
     * The legacy end date is the last day of the mandate, so it is stored as the start of the following day (periods are half-open).
     *
     * @param  array{email: string, first_name?: ?string, last_name?: ?string, phone?: ?string, birth_date?: ?string, study_program?: ?string, organization: string, college: string, seat_number: int|string, started_at: string, ended_at?: ?string}  $data
     *
     * @throws ValidationException When the organization, college or seat cannot be resolved, or the seat capacity is exceeded.
     */
    public function handle(array $data, Authenticatable $approvedBy): Delegate
    {
        return DB::transaction(function () use ($data, $approvedBy): Delegate {
            $seat = $this->resolveSeat($data);
            $user = $this->findOrCreateUser($data);
            $startedAt = Carbon::parse($data['started_at']);

            $existing = Delegate::query()
                ->withoutGlobalScopes([CurrentlyScope::class, ApprovedScope::class])
                ->whereBelongsTo($user)
                ->where('seat_id', $seat->getKey())
                ->whereDate('started_at', $startedAt)
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            return $this->approvalService->createApproved([
                'user_id' => $user->getKey(),
                'organization_id' => $seat->collegeOrganization->organization_id,
                'seat_id' => $seat->getKey(),
                'email' => $user->email,
                'started_at' => $startedAt,
                'ended_at' => filled($data['ended_at'] ?? null) ? Carbon::parse($data['ended_at'])->startOfDay()->addDay() : null,
            ], $approvedBy->getAuthIdentifier());
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function findOrCreateUser(array $data): User
    {
        $email = Str::lower(Str::trim((string) $data['email']));
        $details = array_filter(
            Arr::only($data, self::USER_FIELDS),
            fn (mixed $value): bool => filled($value),
        );

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            if (blank($details['first_name'] ?? null) || blank($details['last_name'] ?? null)) {
                throw ValidationException::withMessages([
                    'first_name' => __('delegate_import.errors.name_required_for_new_user', ['email' => $email]),
                ]);
            }

            return User::create(['email' => $email, ...$details]);
        }

        $missingDetails = array_filter(
            $details,
            fn (mixed $value, string $field): bool => blank($user->{$field}),
            ARRAY_FILTER_USE_BOTH,
        );

        if ($missingDetails !== []) {
            $user->update($missingDetails);
        }

        return $user;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveSeat(array $data): Seat
    {
        $organization = $this->resolveOrganization((string) $data['organization']);
        $college = $this->resolveCollege((string) $data['college']);

        $collegeOrganization = CollegeOrganization::query()
            ->whereBelongsTo($organization)
            ->whereBelongsTo($college)
            ->first();

        if ($collegeOrganization === null) {
            throw ValidationException::withMessages([
                'college' => __('delegate_import.errors.college_organization_not_found', [
                    'organization' => $organization->short_name,
                    'college' => $college->name,
                ]),
            ]);
        }

        $seat = Seat::query()
            ->whereBelongsTo($collegeOrganization)
            ->where('seat_number', (int) $data['seat_number'])
            ->first();

        if ($seat === null) {
            throw ValidationException::withMessages([
                'seat_number' => __('delegate_import.errors.seat_not_found', [
                    'seat_number' => $data['seat_number'],
                    'organization' => $organization->short_name,
                    'college' => $college->name,
                ]),
            ]);
        }

        $seat->setRelation('collegeOrganization', $collegeOrganization);

        return $seat;
    }

    /**
     * Match the short name first, then the legal name; an ambiguous match is refused.
     */
    private function resolveOrganization(string $label): Organization
    {
        $normalized = self::normalizeLabel($label);
        $organizations = Organization::query()->get();

        foreach (['short_name', 'legal_name'] as $attribute) {
            /** @var Collection<int, Organization> $matches */
            $matches = $organizations->filter(
                fn (Organization $organization): bool => filled($organization->{$attribute})
                    && self::normalizeLabel($organization->{$attribute}) === $normalized
            );

            if ($matches->count() > 1) {
                throw ValidationException::withMessages([
                    'organization' => __('delegate_import.errors.organization_ambiguous', ['organization' => $label]),
                ]);
            }

            if ($matches->count() === 1) {
                return $matches->first();
            }
        }

        throw ValidationException::withMessages([
            'organization' => __('delegate_import.errors.organization_not_found', ['organization' => $label]),
        ]);
    }

    private function resolveCollege(string $label): College
    {
        $normalized = self::normalizeLabel($label);

        $matches = College::query()->get()->filter(
            fn (College $college): bool => self::normalizeLabel($college->name) === $normalized
        );

        if ($matches->count() > 1) {
            throw ValidationException::withMessages([
                'college' => __('delegate_import.errors.college_ambiguous', ['college' => $label]),
            ]);
        }

        return $matches->first() ?? throw ValidationException::withMessages([
            'college' => __('delegate_import.errors.college_not_found', ['college' => $label]),
        ]);
    }
}
