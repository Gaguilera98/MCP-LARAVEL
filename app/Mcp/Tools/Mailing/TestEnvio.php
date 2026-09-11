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

#[Name('test-envio')]
#[Description(
    'Manda un correo de prueba real, pero solo a los buzones de prueba que indiques: los participantes de la campaña no reciben nada. '.
    'Elegí con participant_id de quién se toman los datos para rellenar las variables. '.
    'Es la única forma de comprobar que los adjuntos se descargan bien y de ver el correo tal cual llega a la bandeja.'
)]
class TestEnvio extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $accountId = (int) $request->get('account_id');
        $sendId = (int) $request->get('send_id');

        $ids = MailingApi::decodeJsonArg($request->get('test_recipient_ids_json'), 'test_recipient_ids_json');
        if ($ids instanceof Response || $ids instanceof ResponseFactory) {
            return $ids;
        }

        $body = [
            'participant_id' => (int) $request->get('participant_id'),
            'test_recipient_ids' => $ids,
        ];

        $cc = MailingApi::decodeOptionalJsonArg($request->get('cc_test_recipient_ids_json'), 'cc_test_recipient_ids_json');
        if ($cc instanceof Response || $cc instanceof ResponseFactory) {
            return $cc;
        }
        if ($cc !== null) {
            $body['cc_test_recipient_ids'] = $cc;
        }

        return MailingApi::post('accounts/'.$accountId.'/sends/'.$sendId.'/test', $body);
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
            'participant_id' => $schema->integer()
                ->description('Persona de la campaña cuyos datos se usan para rellenar las variables del correo.')
                ->required(),
            'test_recipient_ids_json' => $schema->string()
                ->description('IDs de los buzones de prueba que recibirán el correo, en formato JSON: [1,2]. Se obtienen con listar-destinatarios-prueba.')
                ->required(),
            'cc_test_recipient_ids_json' => $schema->string()
                ->description('Opcional. IDs de buzones de prueba que recibirán el correo en copia, en formato JSON: [3].'),
        ];
    }
}
