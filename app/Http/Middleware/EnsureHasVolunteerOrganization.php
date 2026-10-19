<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Filament\Pages\JoinOrganization;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class EnsureHasVolunteerOrganization
{
    /**
     * Handle an incoming request.
     *
     * Users without an approved, current volunteer, or with one still waiting for its
     * position title, are sent to the join organization page. Besides that page, only
     * logout and email verification stay reachable.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return $next($request);
        }

        if ($request->routeIs(JoinOrganization::ROUTE_NAME, 'filament.root.auth.logout', 'filament.root.auth.email-verification.*')) {
            return $next($request);
        }

        if ($user->needsVolunteerOnboarding()) {
            return redirect()->route(JoinOrganization::ROUTE_NAME);
        }

        return $next($request);
    }
}
