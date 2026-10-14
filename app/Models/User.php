<?php

declare(strict_types=1);

namespace App\Models;

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
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

final class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasRoles;
    use HasUuids;
    use Notifiable;

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
     * Only users with an @afges.org email address may access the panel.
     */
    final public function canAccessPanel(Panel $panel): bool
    {
        return Str::endsWith($this->email, '@afges.org');
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
        return $this->delegateEligibleOrganizations()->exists();
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
