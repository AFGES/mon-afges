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
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Skip if user is not authenticated
        if (! $user) {
            return $next($request);
        }

        // Skip if already on the select organization page
        if ($request->routeIs('filament.admin.pages.select-organization')) {
            return $next($request);
        }

        // Skip if there are no organizations available
        if (Organization::query()->doesntExist()) {
            return $next($request);
        }

        // Check if user has at least one volunteer organization
        if ($user->volunteers()->doesntExist()) {
            return redirect()->route('filament.admin.pages.select-organization');
        }

        return $next($request);
    }
}
