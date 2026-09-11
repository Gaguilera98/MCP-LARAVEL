<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\Mailing\ActualizarCampana;
use App\Mcp\Tools\Mailing\ActualizarCc;
use App\Mcp\Tools\Mailing\ActualizarCliente;
use App\Mcp\Tools\Mailing\ActualizarEnvio;
use App\Mcp\Tools\Mailing\ActualizarParticipante;
use App\Mcp\Tools\Mailing\ActualizarPlantilla;
use App\Mcp\Tools\Mailing\AudienciaEnvio;
use App\Mcp\Tools\Mailing\CompatibilidadPlantilla;
use App\Mcp\Tools\Mailing\CrearCampana;
use App\Mcp\Tools\Mailing\CrearCc;
use App\Mcp\Tools\Mailing\CrearCliente;
use App\Mcp\Tools\Mailing\CrearEnvio;
use App\Mcp\Tools\Mailing\CrearPlantilla;
use App\Mcp\Tools\Mailing\CuotaCuenta;
use App\Mcp\Tools\Mailing\FormatoPlantilla;
use App\Mcp\Tools\Mailing\LaunchEnvio;
use App\Mcp\Tools\Mailing\ListarCampanas;
use App\Mcp\Tools\Mailing\ListarCc;
use App\Mcp\Tools\Mailing\ListarClientes;
use App\Mcp\Tools\Mailing\ListarCuentas;
use App\Mcp\Tools\Mailing\ListarDestinatariosEnvio;
use App\Mcp\Tools\Mailing\ListarDestinatariosPrueba;
use App\Mcp\Tools\Mailing\ListarEnvios;
use App\Mcp\Tools\Mailing\ListarParticipantes;
use App\Mcp\Tools\Mailing\ListarPlantillas;
use App\Mcp\Tools\Mailing\ListarRemitentes;
use App\Mcp\Tools\Mailing\MergeTagsCampana;
use App\Mcp\Tools\Mailing\ObtenerCampana;
use App\Mcp\Tools\Mailing\ObtenerCuenta;
use App\Mcp\Tools\Mailing\ObtenerEnvio;
use App\Mcp\Tools\Mailing\ObtenerPlantilla;
use App\Mcp\Tools\Mailing\PauseEnvio;
use App\Mcp\Tools\Mailing\PreviewEnvio;
use App\Mcp\Tools\Mailing\ResumeEnvio;
use App\Mcp\Tools\Mailing\RetryFailedEnvio;
use App\Mcp\Tools\Mailing\TestEnvio;
use App\Mcp\Tools\Mailing\UpsertParticipantes;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Godai Mailing')]
#[Version('0.1.6')]
#[Instructions(
    'API Godai Mailing bajo /api/v1 con Bearer (MAILING_API_BASE_URL, MAILING_API_TOKEN). '.
    'Argumentos snake_case: account_id, campaign_id, client_id, cc_recipient_id, template_id, send_id, participant_id. '.
    'Bodies anidados van como *_json (string JSON): participants_json, attributes_json, field_schema_json, template_json, audience_filter_json, attachments_json, cc_emails_json, test_recipient_ids_json, recipient_ids_json. '.
    'Adjuntos (attachments_json): SOLO modos fixed_url (misma URL para todos) y field (URL por participante desde un key del field_schema). NUNCA fixed_upload ni multipart. '.
    'Límites de adjunto (obligatorio respetar antes de crear/actualizar envío): cada archivo descargado ≤ 10 MB (tope de la plataforma alineado a Brevo); preferir pocos adjuntos livianos. '.
    'URLs permitidas: https público; Google Drive archivo (…/file/d/ID/view) o open?id= — NO carpetas Drive; Sheets/Docs/Slides de Google (se exportan); URL directa a archivo. '.
    'El enlace debe ser descargable (en Drive: «cualquiera con el enlace»). Si la URL devuelve HTML/login/vista previa, falla. '.
    'Extensiones/formatos recomendados: pdf, png, jpg/jpeg, gif, webp, doc/docx, xls/xlsx, ppt/pptx, txt, csv, zip. Evitar ejecutables (.exe, .bat, .js, .html como adjunto) y archivos >10 MB. '.
    'Si mode=field, el valor del atributo del participante debe ser una URL válida (no un path local). '.
    'Panel primero para remitentes y destinatarios de prueba. Clientes, campañas, plantillas y CC de cliente SÍ por API. '.
    'CC: crear-cc/actualizar-cc en el catálogo del cliente → opcionalmente asignar en campaña (cc_emails_json) y/o en el envío (cc_emails_json). Solo emails del catálogo del cliente de la campaña. '.
    'Plantillas: formato-plantilla, crear-plantilla (from_example o template_json format_version 1), actualizar-plantilla. '.
    'Participantes: upsert-participantes / actualizar-participante; attributes solo keys del field_schema. '.
    'Pruebas (draft send_id): preview-envio/test-envio para plantilla o config del envío. '.
    'Flujo agente: listar-cuentas → crear-cliente → crear-cc (opcional) → crear-campana (client_id + field_schema + cc opcionales) → upsert-participantes → '.
    'formato-plantilla/crear-plantilla → listar-remitentes → compatibilidad-plantilla → crear-envio → audiencia-envio → preview/test → launch-envio. '.
    'CC descartados llegan en warnings. Sin DELETE. No crear remitentes ni destinatarios de prueba desde API en v1. '.
    'Tras cambiar tools PHP locales: reiniciar MCP (toggle o Reload Window).'
)]
class GodaiMailing extends Server
{
    /**
     * Laravel MCP pagina tools/list (default 15). Cursor no siempre sigue nextCursor;
     * con 37 tools hay que devolverlas en una sola página.
     */
    public int $defaultPaginationLength = 50;

    protected array $tools = [
        ListarCuentas::class,
        ObtenerCuenta::class,
        CuotaCuenta::class,
        ListarRemitentes::class,
        ListarClientes::class,
        CrearCliente::class,
        ActualizarCliente::class,
        ListarCc::class,
        CrearCc::class,
        ActualizarCc::class,
        ListarDestinatariosPrueba::class,
        ListarCampanas::class,
        CrearCampana::class,
        ObtenerCampana::class,
        ActualizarCampana::class,
        MergeTagsCampana::class,
        ListarParticipantes::class,
        UpsertParticipantes::class,
        ActualizarParticipante::class,
        ListarPlantillas::class,
        FormatoPlantilla::class,
        CrearPlantilla::class,
        ObtenerPlantilla::class,
        ActualizarPlantilla::class,
        CompatibilidadPlantilla::class,
        ListarEnvios::class,
        CrearEnvio::class,
        ObtenerEnvio::class,
        ActualizarEnvio::class,
        ListarDestinatariosEnvio::class,
        AudienciaEnvio::class,
        PreviewEnvio::class,
        TestEnvio::class,
        LaunchEnvio::class,
        PauseEnvio::class,
        ResumeEnvio::class,
        RetryFailedEnvio::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
