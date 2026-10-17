<?php

namespace App\Http\Middleware;

use App\Support\RoutePermission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards a route with the permission derived from its name by convention
 * (see App\Support\RoutePermission).
 *
 * Behaviour (fail-closed):
 * - Unnamed routes and allowlisted routes (locale, logout, ...) are always
 *   allowed.
 * - Every other named route requires the permission derived from its name,
 *   checked through Spatie so roles are traversed automatically
 *   (User -> Role -> Permission). A deleted/missing permission therefore denies
 *   access; the coverage test makes sure real app routes always have one.
 */
class CheckRoutePermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $name = $request->route()?->getName();

        if ($name !== null && ! RoutePermission::allows($request->user(), $name)) {
            abort(403, __('general.unauthorized'));
        }

        return $next($request);
    }
}
