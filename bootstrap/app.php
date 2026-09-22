<?php

use App\Http\Middleware\AuthenticateMcp;
use App\Http\Middleware\EnsureMcpServerToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth.mcp' => AuthenticateMcp::class,
            'mcp.server' => EnsureMcpServerToken::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'mcp/*',
            'oauth/*',
            '.well-known/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
