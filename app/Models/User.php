<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
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
     * Only users with an @afges.org email address may access the panel.
     */
    final public function canAccessPanel(Panel $panel): bool
    {
        return Str::endsWith($this->email, '@afges.org');
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
}
