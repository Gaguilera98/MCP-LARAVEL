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

#[Name('listar-destinatarios-envio')]
#[Description(
    'Personas incluidas en un envío y cómo les fue. '.
    'Incluye engagement Brevo si ya se sincronizó (delivered_at, opened_at, clicked_at, bounced_at). '.
    'Usá status=failed para ver a quiénes no les llegó y por qué, antes de reintentar con reintentar-fallidos-envio. '.
    'Para refrescar métricas del envío entero usá metricas-envio.'
)]
class ListarDestinatariosEnvio extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $accountId = (int) $request->get('account_id');
        $sendId = (int) $request->get('send_id');

        return MailingApi::get('accounts/'.$accountId.'/sends/'.$sendId.'/recipients', [
            'status' => $request->get('status'),
            'page' => $request->get('page'),
            'per_page' => $request->get('per_page'),
        ]);
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
                ->description('Envío sobre el que actuás.')
                ->required(),
            'status' => $schema->string()
                ->description('Filtrar por resultado: pending (en cola), sent (enviado), failed (falló) o skipped (omitido).'),
            'page' => $schema->integer()
                ->description('Número de página; empieza en 1.'),
            'per_page' => $schema->integer()
                ->description('Cuántos resultados por página; máximo 200.'),
        ];
    }
}
