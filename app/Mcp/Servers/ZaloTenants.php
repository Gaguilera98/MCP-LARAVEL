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
use App\Mcp\Tools\GetAccountUsageByUser;

#[Name('Zalo Tenants')]
#[Version('0.0.1')]
#[Instructions(
    'API bajo /api/v1 con Bearer (ZALO_API_BASE_URL, ZALO_API_TOKEN). Fechas de filtro en query: YYYY-MM-DD. '.
    'Nombres de argumentos: tools bulk get_account_* usan tenantId y accountId (camelCase). El resto (listar-*, generaciones-usuario, chat-usuario, uso-cuenta-tenant) usan tenant_id, account_id (snake_case). '.
    'Flujo segmento (creativo + chat + costo): listar-tenants → listar-cuentas-tenants → get_account_filters → en paralelo get_account_generations y get_account_chat con los mismos filtros; costo agregado de cuenta: uso-cuenta-tenant (incluye user_distribution: % usuarios por área/cargo/empresa sobre el total registrado; esa parte no depende de date_from/date_to; costos y by_tool sí); desglose por usuario: get_account_usage_by_user. '.
    'Flujo usuario puntual: listar-usuarios-cuenta-tenant → generaciones-usuario y chat-usuario; costo de cuenta con uso-cuenta-tenant si hace falta. '.
    'get_account_generations (bulk): solo historial creativo y presentaciones; si tools incluye chat, la API lo ignora (nota en meta.notes). Para conversaciones reales usa get_account_chat o chat-usuario. '.
    'generaciones-usuario con tools=chat devuelve resumen de facturación (UsageRecord), no mensajes de chat; texto de chat → chat-usuario. '.
    'get_account_chat: paginar con include_messages ausente o false; true solo para leer texto. '.
    'get_account_filters: llamar antes de bulks para ids y nombres válidos. '.
    'listar-envios-formulario-tenant: la API no pagina envíos; en tenants con mucho volumen la respuesta puede ser muy grande.'
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
        GetAccountUsageByUser::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
