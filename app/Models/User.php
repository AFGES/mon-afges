<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Models\Scopes\ApprovedScope;
use App\Models\Scopes\CurrentlyScope;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\PasskeyAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

final class User extends Authenticatable implements FilamentUser, MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasRoles;
    use HasUuids;
    use Notifiable;
    use PasskeyAuthenticatable;

    public const string SUPER_ADMIN_ROLE = 'super_admin';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'magic_link_token',
        'magic_link_token_expires_at',
        'birth_date',
        'phone',
        'postal_address',
        'study_program',
        'elected_mandates',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Anyone may sign in: access is gated by email verification, roles and the volunteer organization middleware.
     */
    final public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(self::SUPER_ADMIN_ROLE);
    }

    /** @return HasMany<Volunteer, $this> */
    public function volunteers(): HasMany
    {
        return $this->hasMany(Volunteer::class);
    }

    /** @return HasMany<Delegate, $this> */
    public function delegates(): HasMany
    {
        return $this->hasMany(Delegate::class);
    }

    /**
     * Organizations the user may request a delegate seat for: those where the user
     * is a current, approved volunteer and which have at least one seat.
     *
     * @return Builder<Organization>
     */
    public function delegateEligibleOrganizations(): Builder
    {
        return Organization::query()
            ->whereHas('volunteers', fn (Builder $query) => $query->where('user_id', $this->id))
            ->whereIn('id', CollegeOrganization::query()->whereHas('seatRecords')->select('organization_id'));
    }

    public function canRequestDelegateSeat(): bool
    {
        return once(fn (): bool => $this->delegateEligibleOrganizations()->exists());
    }

    /**
     * Whether the user holds a current seat or has a pending seat request, both of which
     * rely on the personal details mandatory at request time.
     */
    public function hasCurrentOrPendingDelegateSeat(): bool
    {
        return $this->delegates()
            ->withoutGlobalScope(ApprovedScope::class)
            ->whereIn('approval_status', [ApprovalStatus::Pending, ApprovalStatus::Approved])
            ->exists();
    }

    /**
     * Organizations the user is linked to through an approved, current volunteer.
     *
     * @return Collection<int, string>
     */
    public function linkedOrganizationIds(): Collection
    {
        return $this->volunteers()->pluck('organization_id')->unique()->values();
    }

    /**
     * The pending request for an existing organization the user is not linked to yet.
     * It locks requests for other new organizations until it is processed.
     *
     * @param  Collection<int, string>|null  $linkedOrganizationIds  reuses a computed linkedOrganizationIds()
     */
    public function pendingOrganizationRequest(?Collection $linkedOrganizationIds = null): ?Volunteer
    {
        return $this->volunteers()
            ->withoutGlobalScopes([ApprovedScope::class, CurrentlyScope::class])
            ->pending()
            ->whereHas('organization')
            ->whereNotIn('organization_id', $linkedOrganizationIds ?? $this->linkedOrganizationIds())
            ->with('organization')
            ->oldest()
            ->first();
    }

    /**
     * Whether the user must go through the join organization page before using the panel:
     * no approved, current volunteer yet, or one still waiting for its position title.
     * Super admins and applications without any organization are never held back.
     * A single query checks both volunteer conditions.
     */
    public function needsVolunteerOnboarding(): bool
    {
        if ($this->isSuperAdmin()) {
            return false;
        }

        if (Organization::query()->doesntExist()) {
            return false;
        }

        $volunteerState = self::query()
            ->whereKey($this->getKey())
            ->select('id')
            ->withExists([
                'volunteers as has_volunteer',
                'volunteers as awaits_position_title' => fn (Builder $query) => $query->scopes(['awaitingPositionTitle']),
            ])
            ->toBase()
            ->first();

        return ! $volunteerState?->has_volunteer || $volunteerState->awaits_position_title;
    }

    /**
     * The full name, kept in sync with the generated `name` column so it is
     * available before the model is refreshed from the database.
     *
     * @return Attribute<string, never>
     */
    protected function name(): Attribute
    {
        return Attribute::get(function (?string $value, array $attributes): string {
            if (! array_key_exists('first_name', $attributes) && ! array_key_exists('last_name', $attributes)) {
                return (string) $value;
            }

            return Str::squish(($attributes['first_name'] ?? '').' '.($attributes['last_name'] ?? ''));
        });
    }

    /**
     * Emails are stored trimmed and lowercase so every lookup can match them exactly.
     *
     * @return Attribute<never, string>
     */
    protected function email(): Attribute
    {
        return Attribute::set(fn (string $value): string => Str::lower(Str::trim($value)));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    final protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'magic_link_token_expires_at' => 'datetime',
            'birth_date' => 'date',
        ];
    }
}
