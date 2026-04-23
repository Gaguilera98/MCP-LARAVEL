<?php

use Laravel\Mcp\Facades\Mcp;
use App\Mcp\Servers\ZaloTenants;

// Mcp::web('/mcp/demo', \App\Mcp\Servers\PublicServer::class);
Mcp::web('/mcp/zalo-tenants', ZaloTenants::class);
Mcp::local('zalo-tenants', ZaloTenants::class);
