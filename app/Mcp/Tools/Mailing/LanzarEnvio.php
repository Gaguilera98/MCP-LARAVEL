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

#[Name('lanzar-envio')]
#[Description(
    'ENVÍA EL CORREO DE VERDAD a toda la audiencia del envío. Es irreversible: confirmalo con la persona antes de usarla. '.
    'Consultá primero audiencia-envio y pasá ese mismo número en confirm_recipient_count; si no coincide no se envía nada. '.
    'Para probar sin afectar a nadie usá previsualizar-envio o probar-envio.'
)]
class LanzarEnvio extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $accountId = (int) $request->get('account_id');
        $sendId = (int) $request->get('send_id');

        return MailingApi::post('accounts/'.$accountId.'/sends/'.$sendId.'/launch', [
            'confirm_recipient_count' => (int) $request->get('confirm_recipient_count'),
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
            'confirm_recipient_count' => $schema->integer()
                ->description('Cantidad exacta de personas que va a recibir el correo: el valor valid_emails que devuelve audiencia-envio.')
                ->required(),
        ];
    }
}
