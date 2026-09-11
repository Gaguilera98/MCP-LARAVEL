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

#[Name('previsualizar-envio')]
#[Description(
    'Muestra cómo queda el correo para una persona concreta, sin enviar nada. '.
    'Sirve para revisar la plantilla y también la configuración del envío (asunto propio, filtro). '.
    'Avisa qué variables quedaron sin valor. Los adjuntos no se descargan acá: eso se comprueba con probar-envio.'
)]
class PrevisualizarEnvio extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $accountId = (int) $request->get('account_id');
        $sendId = (int) $request->get('send_id');

        return MailingApi::post('accounts/'.$accountId.'/sends/'.$sendId.'/preview', [
            'participant_id' => (int) $request->get('participant_id'),
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
            'participant_id' => $schema->integer()
                ->description('Persona de la campaña cuyos datos se usan para rellenar las variables del correo.')
                ->required(),
        ];
    }
}
