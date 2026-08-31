<?php

namespace App\Mcp\Servers;

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use App\Mcp\Tools\ListarCuentasTenants;
use App\Mcp\Tools\ListarEnviosFormularioTenant;
use App\Mcp\Tools\ListarFormulariosTenant;
use App\Mcp\Tools\ListarUsuariosFormularioTenant;
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
use App\Mcp\Tools\ListarModelos;

#[Name('Zalo Tenants')]
#[Version('0.0.3')]
#[Instructions(
    'API bajo /api/v1 con Bearer (ZALO_API_BASE_URL, ZALO_API_TOKEN). Fechas de filtro en query: YYYY-MM-DD. '.
    'Nombres de argumentos: tools bulk get_account_* usan tenantId y accountId (camelCase). El resto (listar-*, generaciones-usuario, chat-usuario, uso-cuenta-tenant, listar-modelos) usan tenant_id, account_id (snake_case). '.
    'Flujo segmento (creativo + chat + costo): listar-tenants → listar-cuentas-tenants → get_account_filters → en paralelo get_account_generations y get_account_chat con los mismos filtros; costo agregado: uso-cuenta-tenant (by_tool, by_model, integrity, user_distribution); desglose por usuario: get_account_usage_by_user. '.
    'Catálogo: listar-modelos (tenant_id) → platform, status active|inactive, available_until, days_until_expiration, availability, pricing. Luego filtrar generations con model/platform/status si hace falta. '.
    'Flujo usuario puntual: listar-usuarios-cuenta-tenant → generaciones-usuario y chat-usuario; costo de cuenta con uso-cuenta-tenant si hace falta. '.
    'get_account_generations / generaciones-usuario: ítems nuevos pueden traer usage_record_id y cost_usd (T1; histórico pre-migración null sin backfill). Costo de UNA pieza → cost_usd del ítem; uso-cuenta-tenant es agregado. Filtros opcionales model, platform, status. Fallidos (status=failed) visibles post-T7. '.
    'Video en generations: duration_seconds, resolution, has_audio, source_images[], source_type (text_to_video|image_to_video), count — desde input_payload; null si no estaba guardado. seed/fps/negative_prompt no se capturan. '.
    'get_account_generations: solo historial creativo y presentaciones; si tools incluye chat, la API lo ignora (meta.notes). Chat real → get_account_chat o chat-usuario. '.
    'generaciones-usuario con tools=chat: resumen UsageRecord, no mensajes; texto → chat-usuario. '.
    'get_account_chat / chat-usuario (T11): sesión con model_name, total_tokens, total_cost_usd, usage_conversation_key, started_at; paginar con include_messages false; true solo para leer texto (model_used, tokens por mensaje; tokens en role=user suelen ser null). date_from/date_to filtran por última actividad (message_created casteado a DATETIME; OK usar fechas del día). '.
    'Costos: uso-cuenta-tenant by_model[] prefiere platform de usage_metrics (T5+, registros nuevos); si no, proveedor del catálogo. integrity{} compara historial vs usage. '.
    'get_account_filters: llamar antes de bulks para ids y nombres válidos. '.
    'Flujo formulario: listar-tenants → listar-formularios-tenant → listar-usuarios-formulario-tenant (assigned_users) → listar-envios-formulario-tenant u obtener-envio-formulario-tenant. '.
    'listar-envios-formulario-tenant: la API no pagina envíos; en tenants con mucho volumen la respuesta puede ser muy grande. '.
    'Tras cambiar tools PHP locales: reiniciar MCP (toggle o Reload Window). Prod (zalo-tenants-prod) puede ir atrasado hasta redeploy.'
)]
class ZaloTenants extends Server
{
    protected array $tools = [
        ListarTenants::class,
        ListarCuentasTenants::class,
        UsoCuentaTenant::class,
        ListarFormulariosTenant::class,
        ListarUsuariosFormularioTenant::class,
        ListarEnviosFormularioTenant::class,
        ObtenerEnvioFormularioTenant::class,
        ListarUsuariosCuentaTenant::class,
        GeneracionesUsuario::class,
        ChatUsuario::class,
        GetAccountGenerations::class,
        GetAccountFilters::class,
        GetAccountChat::class,
        GetAccountUsageByUser::class,
        ListarModelos::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
