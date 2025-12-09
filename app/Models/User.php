<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

final class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    use HasRoles;

    protected static function booted(): void
    {
        self::created(function (User $user) {
            if ($user->email) {
                $user->syncPrimaryEmail();
            }
        });

        self::updated(function (User $user) {
            if ($user->wasChanged('email')) {
                $user->syncPrimaryEmail();
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'magic_link_token',
        'magic_link_token_expires_at',
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
        ];
    }

    /**
     * Permission for access to the panel
     */
    final public function canAccessPanel(Panel $panel): bool
    {
        return Str::endsWith($this->email, '@afges.org');
    }

    /**
     * Get all emails for the user.
     */
    public function emails(): HasMany
    {
        return $this->hasMany(Email::class);
    }

    /**
     * Get the primary email for the user.
     */
    public function primaryEmail(): ?Email
    {
        return $this->emails()->where('is_primary', true)->first();
    }

    /**
     * Get all verified emails for the user.
     */
    public function verifiedEmails(): HasMany
    {
        return $this->emails()->whereNotNull('verified_at');
    }

    /**
     * Get all volunteer positions for the user.
     */
    public function volunteers(): HasMany
    {
        return $this->hasMany(Volunteer::class);
    }

    /**
     * Sync the email field with the primary email record.
     */
    public function syncPrimaryEmail(): void
    {
        $primaryEmail = $this->primaryEmail();

        if ($primaryEmail && $primaryEmail->email !== $this->email) {
            // Update existing primary email
            $primaryEmail->email = $this->email;
            $primaryEmail->save();
        } elseif (! $primaryEmail && $this->email) {
            // Create new primary email
            $this->emails()->create([
                'email' => $this->email,
                'is_primary' => true,
                'verified_at' => $this->email_verified_at,
            ]);
        }
    }
}
