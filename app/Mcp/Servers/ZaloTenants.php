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
#[Version('0.0.5')]
#[Instructions(
    'API bajo /api/v1 con Bearer (ZALO_API_BASE_URL, ZALO_API_TOKEN). Fechas de filtro en query: YYYY-MM-DD. '.
    'Nombres de argumentos: tools bulk get_account_* usan tenantId y accountId (camelCase). El resto (listar-*, generaciones-usuario, chat-usuario, uso-cuenta-tenant, listar-modelos) usan tenant_id, account_id (snake_case). '.
    'Flujo segmento (creativo + chat + costo): listar-tenants → listar-cuentas-tenants → get_account_filters → en paralelo get_account_generations y get_account_chat con los mismos filtros; costo agregado: uso-cuenta-tenant (by_tool, by_model, integrity, user_distribution); desglose por usuario: get_account_usage_by_user. '.
    'Catálogo: listar-modelos (tenant_id) → platform, status active|inactive, available_until, days_until_expiration, availability, pricing (unit_type + unit_definition; markup_percentage separa costo proveedor vs precio venta). Filtrar generations con model= el id/slug del catálogo (no la etiqueta display_name). by_model[] trae model_id, display_name, count. model= también acepta la etiqueta o mayúsculas/-/_ ; agrupa al mismo slug. '.
    'Precios por resolución en catálogo: unit_definition puede traer prices_by_resolution (ej. ltx-2.5-fast, minimax-h3, seedream-5-pro) o high_res_price_per_second (sora-2-pro). Cruzar con resolution y duration_seconds del ítem de generations para estimar costo esperado vs cost_usd cobrado. '.
    'Flujo usuario puntual: listar-usuarios-cuenta-tenant → generaciones-usuario y chat-usuario; costo de cuenta con uso-cuenta-tenant si hace falta. '.
    'get_account_generations / generaciones-usuario: cada ítem puede traer usage_record_id y cost_usd (null si ese ítem no tiene registro de costo ligado). Costo de UNA pieza → cost_usd del ítem; uso-cuenta-tenant es agregado. Ítem.model y by_model[].model_id son slug canónico; display_name es la etiqueta. platform va a nivel ítem. Filtros opcionales model, platform, status (incl. failed). '.
    'Video en generations: duration_seconds, resolution, has_audio, source_images[], source_type (text_to_video|image_to_video), count se leen desde input_payload (null solo si la clave no está en el payload). results[].ratio suele venir en video. seed/fps/negative_prompt no están en la API. '.
    'get_account_generations: solo historial creativo y presentaciones; si tools incluye chat, la API lo ignora (meta.notes). Chat real → get_account_chat o chat-usuario. '.
    'generaciones-usuario con tools=chat: resumen UsageRecord, no mensajes; texto → chat-usuario. '.
    'get_account_chat / chat-usuario: sesión con model_name, total_tokens, total_cost_usd, conversation_id, status, usage_conversation_key, started_at, duration_seconds; paginar con include_messages false; true solo para leer texto (model_used, tokens por mensaje; tokens en role=user suelen ser null). Timestamp de mensaje: created_at. date_from/date_to filtran por última actividad (OK usar fechas del día). '.
    'Adjuntos chat: con include_messages=true, mensajes user pueden traer attachments[] (image|external_file) con url y s3_key; hasta 5 imgs + 5 docs. '.
    'Adjuntos contables: cada conversación trae attachments_summary {count, by_type:{image,document}, total_bytes}; meta.summary igual + conversations_with_attachments. Filtrar con has_attachments=true|false sin include_messages (una llamada para contar PDFs/imágenes en un periodo). '.
    'Costos: uso-cuenta-tenant by_model[] usa platform = nombre del proveedor del catálogo (como listar-modelos: Google, OpenAI); no el slug runtime (gemini/openai). integrity{} compara historial creativo vs usage (imagen/video/prompt) a nivel cuenta y fechas; no cambia con page de by-user. Chat y presentaciones no entran. '.
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
