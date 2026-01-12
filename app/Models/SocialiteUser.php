<?php

declare(strict_types=1);

namespace App\Models;

use DutchCodingCompany\FilamentSocialite\Models\SocialiteUser as BaseSocialiteUser;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

final class SocialiteUser extends BaseSocialiteUser
{
    use HasUuids;
}
