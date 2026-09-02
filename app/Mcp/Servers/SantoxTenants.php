<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\Santox\ListarCuentasTenants;
use App\Mcp\Tools\Santox\ListarTenants;
use App\Mcp\Tools\Santox\ListarUsuariosCuentaTenant;
use App\Mcp\Tools\Santox\UsoCuentaTenant;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Santox Tenants')]
#[Version('0.0.2')]
#[Instructions(
    'API central Santox bajo /api/v1 con Bearer (SANTOX_API_BASE_URL, SANTOX_API_TOKEN). '.
    'Host = dominio central (panel /admin), no el dominio de un sitio tenant. '.
    'Token = Personal Access Token de user central (Admin → Tokens API central); no uses tokens del panel cliente. '.
    'Flujo: listar-tenants → listar-cuentas-tenants (tenant_id) → listar-usuarios-cuenta-tenant y/ o uso-cuenta-tenant (tenant_id, account_id; date_from/date_to YYYY-MM-DD). '.
    'uso-cuenta-tenant: kpis + by_application + by_provider + by_model + by_day (BillingAnalyticsService). '.
    'Argumentos snake_case: tenant_id, account_id. '.
    'La API de lotes por tenant no forma parte de este server. '.
    'Tras cambiar tools PHP locales: reiniciar MCP (toggle o Reload Window).'
)]
class SantoxTenants extends Server
{
    protected array $tools = [
        ListarTenants::class,
        ListarCuentasTenants::class,
        ListarUsuariosCuentaTenant::class,
        UsoCuentaTenant::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
