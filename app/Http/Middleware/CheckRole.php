<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Garde par rôle (hiérarchie : admin > manager > serveur/cuisine > client).
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        foreach ($roles as $role) {
            if ($user && $user->canAccess($role)) {
                return $next($request);
            }
        }

        $role = $user?->role ?? 'invité';

        abort(403, "Accès interdit : rôle insuffisant ({$role}).");
    }
}
