<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
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

        if ($user->hasRole('super_admin')) {
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

        // Check if user has at least one approved volunteer organization
        // (The ApprovedScope automatically filters for approved volunteers)
        if ($user->volunteers()->doesntExist()) {
            return redirect()->route('filament.root.pages.my-profile');
        }

        return $next($request);
    }
}
