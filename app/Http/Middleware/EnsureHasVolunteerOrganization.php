<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class EnsureHasVolunteerOrganization
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return $next($request);
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if ($request->routeIs('filament.root.pages.my-profile')) {
            return $next($request);
        }

        if ($request->routeIs('filament.root.auth.logout')) {
            return $next($request);
        }

        if (Organization::query()->doesntExist()) {
            return $next($request);
        }

        // One query checks for at least one approved, current volunteer (default scopes)
        // and for one still waiting for its position title.
        $volunteerState = User::query()
            ->whereKey($user->getKey())
            ->select('id')
            ->withExists([
                'volunteers as has_volunteer',
                'volunteers as awaits_position_title' => fn (Builder $query) => $query->scopes(['awaitingPositionTitle']),
            ])
            ->toBase()
            ->first();

        if (! $volunteerState?->has_volunteer || $volunteerState->awaits_position_title) {
            return redirect()->route('filament.root.pages.my-profile');
        }

        return $next($request);
    }
}
