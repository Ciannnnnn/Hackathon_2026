<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $currentRole = $user?->role;
        $roleValue = $currentRole instanceof UserRole ? $currentRole->value : $currentRole;

        abort_unless($user?->is_active && in_array($roleValue, $roles, true), 403);

        return $next($request);
    }
}
