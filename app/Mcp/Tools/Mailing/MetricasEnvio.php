<?php

namespace App\Mcp\Tools\Mailing;

use App\Support\MailingApi;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('metricas-envio')]
#[Description(
    'Métricas Brevo de un envío ya lanzado: entregados, abiertos, clics, rebotes y eventos recientes. '.
    'Por defecto consulta Brevo y actualiza los números antes de responder (como el botón Actualizar métricas del panel), '.
    'así el agente no depende de un webhook. Usá refresh=false solo si querés lo cacheado en base de datos. '.
    'Solo aplica a envíos hechos después de activar el tracking por tags.'
)]
class MetricasEnvio extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $accountId = (int) $request->get('account_id');
        $sendId = (int) $request->get('send_id');

        $refresh = $request->get('refresh');
        if ($refresh === null || $refresh === '') {
            $refresh = true;
        }

        $force = $request->get('force');
        if ($force === null || $force === '') {
            $force = false;
        }

        $query = [
            'refresh' => filter_var($refresh, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
            'force' => filter_var($force, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
        ];

        if ($request->get('events_limit') !== null && $request->get('events_limit') !== '') {
            $query['events_limit'] = (int) $request->get('events_limit');
        }

        return MailingApi::get(
            'accounts/'.$accountId.'/sends/'.$sendId.'/metrics',
            $query,
            timeout: 90,
        );
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'account_id' => $schema->integer()
                ->description('Cuenta sobre la que trabajás; se obtiene con listar-cuentas.')
                ->required(),
            'send_id' => $schema->integer()
                ->description('Envío sobre el que actuás; se obtiene con listar-envios u obtener-envio.')
                ->required(),
            'refresh' => $schema->boolean()
                ->description('Si es true (default), consulta Brevo y actualiza métricas antes de responder. false = solo cache local.'),
            'force' => $schema->boolean()
                ->description('Si es true, fuerza sync aunque haya una actualización reciente (ignora cooldown de 30s).'),
            'events_limit' => $schema->integer()
                ->description('Cuántos eventos recientes devolver (1-100, default 50).'),
        ];
    }
}
