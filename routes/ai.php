<?php

use App\Mcp\Servers\SantoxTenants;
use App\Mcp\Servers\ZaloTenants;
use Laravel\Mcp\Facades\Mcp;

// Mcp::web('/mcp/demo', \App\Mcp\Servers\PublicServer::class);
Mcp::web('/mcp/zalo-tenants', ZaloTenants::class);
Mcp::local('zalo-tenants', ZaloTenants::class);

Mcp::web('/mcp/santox-tenants', SantoxTenants::class);
Mcp::local('santox-tenants', SantoxTenants::class);
