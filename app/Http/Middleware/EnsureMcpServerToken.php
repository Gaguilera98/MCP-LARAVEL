<?php

namespace App\Http\Middleware;

use App\Support\McpServers;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMcpServerToken
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $serverId): Response
    {
        McpServers::assertValid($serverId);

        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'message' => 'This token is not allowed for this MCP server.',
            ], 403);
        }

        // OAuth (Passport): authenticated user is enough (MCP scopes are a bridge to User).
        if ($request->attributes->get('mcp_auth_driver') === 'passport') {
            return $next($request);
        }

        // Sanctum PAT: require ability matching the server id.
        if ($request->attributes->get('mcp_auth_driver') === 'sanctum') {
            $token = $request->attributes->get('mcp_sanctum_token');

            if ($token === null || ! $token->can($serverId)) {
                return response()->json([
                    'message' => 'This token is not allowed for this MCP server.',
                ], 403);
            }

            return $next($request);
        }

        // Fallback: Passport tokenCan / Sanctum via access token adapter.
        $accessToken = method_exists($user, 'currentAccessToken') ? $user->currentAccessToken() : null;

        if ($accessToken instanceof SanctumTokenAdapter) {
            if (! $accessToken->can($serverId)) {
                return response()->json([
                    'message' => 'This token is not allowed for this MCP server.',
                ], 403);
            }

            return $next($request);
        }

        if ($accessToken !== null) {
            return $next($request);
        }

        return response()->json([
            'message' => 'This token is not allowed for this MCP server.',
        ], 403);
    }
}
