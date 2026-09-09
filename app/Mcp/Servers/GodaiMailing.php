<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\Mailing\ActualizarEnvio;
use App\Mcp\Tools\Mailing\ActualizarParticipante;
use App\Mcp\Tools\Mailing\AudienciaEnvio;
use App\Mcp\Tools\Mailing\CompatibilidadPlantilla;
use App\Mcp\Tools\Mailing\CrearEnvio;
use App\Mcp\Tools\Mailing\CuotaCuenta;
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
#[Version('0.1.0')]
#[Instructions(
    'API Godai Mailing bajo /api/v1 con Bearer (MAILING_API_BASE_URL, MAILING_API_TOKEN). '.
    'Argumentos snake_case: account_id, campaign_id, template_id, send_id, participant_id. '.
    'Bodies anidados van como *_json (string JSON): participants_json, attributes_json, audience_filter_json, attachments_json, cc_emails_json, test_recipient_ids_json, recipient_ids_json. '.
    'Adjuntos solo modos fixed_url y field (no fixed_upload). '.
    'Panel primero: campañas, plantillas, remitentes, clientes y CC se crean en el panel; el agente opera sobre ellos. '.
    'Flujo agente: listar-cuentas → listar-campanas/obtener-campana/merge-tags-campana → listar-plantillas → listar-remitentes → '.
    'upsert-participantes → compatibilidad-plantilla → crear-envio → audiencia-envio → preview-envio (opcional) → test-envio → '.
    'launch-envio (confirm_recipient_count = valid_emails de audiencia-envio; si no coincide 409 sin enviar) → '.
    'si fallan: listar-destinatarios-envio status=failed → retry-failed-envio. '.
    'CC descartados llegan en warnings (no silenciosos). cuota-cuenta consulta Brevo real (sendLimit). '.
    'Sin DELETE. No crear campañas/plantillas/clientes desde API en v1. '.
    'Tras cambiar tools PHP locales: reiniciar MCP (toggle o Reload Window).'
)]
class GodaiMailing extends Server
{
    /**
     * Laravel MCP pagina tools/list (default 15). Cursor no siempre sigue nextCursor;
     * con 28 tools hay que devolverlas en una sola página.
     */
    public int $defaultPaginationLength = 50;

    protected array $tools = [
        ListarCuentas::class,
        ObtenerCuenta::class,
        CuotaCuenta::class,
        ListarRemitentes::class,
        ListarClientes::class,
        ListarCc::class,
        ListarDestinatariosPrueba::class,
        ListarCampanas::class,
        ObtenerCampana::class,
        MergeTagsCampana::class,
        ListarParticipantes::class,
        UpsertParticipantes::class,
        ActualizarParticipante::class,
        ListarPlantillas::class,
        ObtenerPlantilla::class,
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
