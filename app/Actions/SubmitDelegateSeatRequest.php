<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Delegate;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final class SubmitDelegateSeatRequest
{
    public const array USER_FIELDS = [
        'last_name',
        'first_name',
        'study_program',
        'birth_date',
        'phone',
        'postal_address',
        'elected_mandates',
    ];

    /**
     * Save the user's personal details and register their pending delegate seat request.
     *
     * @param  array<string, mixed>  $data  Validated form data.
     */
    public function handle(User $user, array $data): Delegate
    {
        return DB::transaction(function () use ($user, $data): Delegate {
            $user->update(Arr::only($data, self::USER_FIELDS));

            return Delegate::create([
                'user_id' => $user->id,
                'organization_id' => $data['organization_id'],
                'seat_id' => $data['seat_id'],
                'email' => $data['email'],
                'started_at' => $data['started_at'],
            ]);
        });
    }
}
