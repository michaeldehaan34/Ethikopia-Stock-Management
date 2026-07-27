<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures the user is logged in via the custom role-based session
 * (preserving the original Flask login flow).
 */
class SessionAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // The custom role-based login flow stores 'username' in the session
        // (see RoleLoginController). If it is missing the user is not logged in.
        if (! $request->session()->has('username')) {
            // Redirect to the migrated role-based login page.
            //
            // We use the literal path '/login' instead of redirect()->route('login')
            // on purpose: if the named 'login' route is ever missing (e.g. route
            // cache cleared, auth.php not loaded), route('login') throws a
            // RouteNotFoundException which Laravel surfaces as an HTTP 500.
            // A literal path can never throw, so the middleware stays safe.
            return redirect('/login');
        }

        return $next($request);
    }
}