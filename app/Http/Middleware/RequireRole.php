<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// D4: replaces the source requireServerAuthContext('role'). Auth + verified are
// applied separately; this enforces the role for a route group and bounces a
// mismatched user to their own dashboard.
class RequireRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            abort(403);
        }

        if (! $user->hasRole($role)) {
            return redirect($user->dashboardPath());
        }

        return $next($request);
    }
}
