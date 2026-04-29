<?php

namespace App\Mcp\Servers;

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use App\Mcp\Tools\ListarCuentasTenants;
use App\Mcp\Tools\ListarEnviosFormularioTenant;
use App\Mcp\Tools\ListarFormulariosTenant;
use App\Mcp\Tools\ListarTenants;
use App\Mcp\Tools\ListarUsuariosCuentaTenant;
use App\Mcp\Tools\ObtenerEnvioFormularioTenant;
use App\Mcp\Tools\GeneracionesUsuario;
use App\Mcp\Tools\ChatUsuario;
use App\Mcp\Tools\UsoCuentaTenant;
use App\Mcp\Tools\GetAccountGenerations;
use App\Mcp\Tools\GetAccountFilters;
use App\Mcp\Tools\GetAccountChat;

#[Name('Zalo Tenants')]
#[Version('0.0.1')]
#[Instructions(
    'Este MCP consulta la API de Zalo: tenants, cuentas, uso por cuenta y fechas, formularios y envíos, usuarios por cuenta, generaciones creativas (por usuario o por cuenta) y chat (por usuario, por cuenta en bulk). '.
    'La URL base y el token están en el entorno (ZALO_API_BASE_URL, ZALO_API_TOKEN). '.
    'Flujo recomendado por cuenta: primero get_account_filters para obtener áreas, empresas y cargos (ids y nombres); luego usa los mismos filtros organizacionales en get_account_generations y/o get_account_chat (area_id, area, company_id, position_id, user_id) para alinear consultas. '.
    'Para evaluar creativo más chat en la misma área o segmento, combina get_account_generations y get_account_chat con los mismos tenantId, accountId y filtros de organización y fechas. '.
    'En chat por cuenta, empieza sin include_messages o con false y pagina; activa include_messages=true solo si necesitas el texto de los mensajes.'
)]
class ZaloTenants extends Server
{
    protected array $tools = [
        ListarTenants::class,
        ListarCuentasTenants::class,
        UsoCuentaTenant::class,
        ListarFormulariosTenant::class,
        ListarEnviosFormularioTenant::class,
        ObtenerEnvioFormularioTenant::class,
        ListarUsuariosCuentaTenant::class,
        GeneracionesUsuario::class,
        ChatUsuario::class,
        GetAccountGenerations::class,
        GetAccountFilters::class,
        GetAccountChat::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
