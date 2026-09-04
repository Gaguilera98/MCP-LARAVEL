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

        if ($user === null || ! $user->tokenCan($serverId)) {
            return response()->json([
                'message' => 'This token is not allowed for this MCP server.',
            ], 403);
        }

        return $next($request);
    }
}
