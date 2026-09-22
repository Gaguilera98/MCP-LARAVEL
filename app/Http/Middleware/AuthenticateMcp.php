<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dual auth for MCP HTTP: Sanctum PATs (Cursor) or Passport OAuth (Claude).
 *
 * Sanctum tokens (`id|secret`) are tried first so Passport does not clear the
 * Authorization header when JWT validation fails on a PAT.
 */
class AuthenticateMcp
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $bearer = $request->bearerToken();

        if ($bearer !== null && $bearer !== '' && str_contains($bearer, '|')) {
            if ($this->authenticateSanctum($request, $bearer)) {
                return $next($request);
            }

            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($this->authenticatePassport($request)) {
            return $next($request);
        }

        return response()->json(['message' => 'Unauthenticated.'], 401);
    }

    protected function authenticatePassport(Request $request): bool
    {
        $guard = Auth::guard('api');

        if (! $guard->check()) {
            return false;
        }

        Auth::shouldUse('api');
        $request->attributes->set('mcp_auth_driver', 'passport');

        return true;
    }

    protected function authenticateSanctum(Request $request, string $bearer): bool
    {
        $accessToken = PersonalAccessToken::findToken($bearer);

        if ($accessToken === null || $accessToken->tokenable === null) {
            return false;
        }

        if ($accessToken->expires_at !== null && $accessToken->expires_at->isPast()) {
            return false;
        }

        /** @var User $user */
        $user = $accessToken->tokenable;

        $user->withAccessToken(new SanctumTokenAdapter($accessToken));
        $accessToken->forceFill(['last_used_at' => now()])->save();

        Auth::setUser($user);
        $request->setUserResolver(static fn () => $user);
        $request->attributes->set('mcp_auth_driver', 'sanctum');
        $request->attributes->set('mcp_sanctum_token', $accessToken);

        return true;
    }
}
