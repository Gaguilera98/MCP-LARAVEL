<?php

use App\Http\Middleware\ForceJsonAccept;
use App\Mcp\Servers\GodaiMailing;
use App\Mcp\Servers\SantoxTenants;
use App\Mcp\Servers\ZaloTenants;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/zalo-tenants', ZaloTenants::class)
    ->middleware([ForceJsonAccept::class, 'auth:sanctum', 'mcp.server:zalo-tenants', 'throttle:mcp']);

Mcp::local('zalo-tenants', ZaloTenants::class);

Mcp::web('/mcp/santox-tenants', SantoxTenants::class)
    ->middleware([ForceJsonAccept::class, 'auth:sanctum', 'mcp.server:santox-tenants', 'throttle:mcp']);

Mcp::local('santox-tenants', SantoxTenants::class);

Mcp::web('/mcp/godai-mailing', GodaiMailing::class)
    ->middleware([ForceJsonAccept::class, 'auth:sanctum', 'mcp.server:godai-mailing', 'throttle:mcp']);

Mcp::local('godai-mailing', GodaiMailing::class);
