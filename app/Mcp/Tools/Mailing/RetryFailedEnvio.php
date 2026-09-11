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

#[Name('retry-failed-envio')]
#[Description(
    'Vuelve a intentar el envío con las personas a las que no les llegó. Envía correos reales. '.
    'Si no indicás nada reintenta con todas las fallidas; con recipient_ids_json elegís solo algunas.'
)]
class RetryFailedEnvio extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $accountId = (int) $request->get('account_id');
        $sendId = (int) $request->get('send_id');

        $body = [];
        $ids = MailingApi::decodeOptionalJsonArg($request->get('recipient_ids_json'), 'recipient_ids_json');
        if ($ids instanceof Response || $ids instanceof ResponseFactory) {
            return $ids;
        }
        if ($ids !== null) {
            $body['recipient_ids'] = $ids;
        }

        return MailingApi::post('accounts/'.$accountId.'/sends/'.$sendId.'/retry-failed', $body);
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
            'recipient_ids_json' => $schema->string()
                ->description('Opcional. IDs de los destinatarios a reintentar, en formato JSON: [12,15]. Si lo omitís se reintenta con todos los que fallaron.'),
        ];
    }
}
